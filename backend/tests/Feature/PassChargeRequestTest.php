<?php

namespace Tests\Feature;

use App\Contracts\SmsGateway;
use App\Models\BusStop;
use App\Models\Fare;
use App\Models\Pass;
use App\Models\PassChargeRequest;
use App\Models\Ride;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PassChargeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['sms.webhook_secret' => 'test-secret']);
    }

    public function test_driver_can_request_approval_on_the_customer_phone(): void
    {
        [$pass] = $this->createPass(20, '4321', '0244000111', Pass::APPROVAL_CUSTOMER_PHONE);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);
        $sms = $this->fakeSms();

        Sanctum::actingAs($driver);

        $this->postJson("/api/passes/{$pass->id}/charge-requests", [
            'route_id' => $route->id,
        ])
            ->assertCreated()
            ->assertJsonPath('request.status', PassChargeRequest::STATUS_PENDING)
            ->assertJsonPath('request.amount', 3.5)
            ->assertJsonPath('request.destination', 'Kaneshie')
            ->assertJsonPath('request.customer_phone', '233 ••• 0111')
            ->assertJsonPath('request.seconds_left', 180)
            ->assertJsonPath('pass.approval_channel', Pass::APPROVAL_CUSTOMER_PHONE);

        $this->assertCount(1, $sms->sent);
        $this->assertSame('233244000111', $sms->sent[0]['to']);
        $this->assertStringContainsString('GHS 3.50 to Kaneshie', $sms->sent[0]['message']);
        $this->assertStringContainsString('Reply with your 4-digit PIN', $sms->sent[0]['message']);

        $this->assertDatabaseHas('pass_charge_requests', [
            'pass_id' => $pass->id,
            'driver_id' => $driver->id,
            'status' => PassChargeRequest::STATUS_PENDING,
            'customer_phone' => '233244000111',
        ]);
    }

    public function test_request_is_rejected_when_phone_approval_is_off(): void
    {
        [$pass] = $this->createPass(20, '4321', '0244000111', Pass::APPROVAL_DEVICE_PIN);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);

        Sanctum::actingAs($driver);

        $this->postJson("/api/passes/{$pass->id}/charge-requests", ['route_id' => $route->id])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'approval_channel_required');

        $this->assertSame(0, PassChargeRequest::count());
    }

    public function test_request_needs_a_valid_customer_phone(): void
    {
        [$pass] = $this->createPass(20, '4321', null, Pass::APPROVAL_CUSTOMER_PHONE);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);

        Sanctum::actingAs($driver);

        $this->postJson("/api/passes/{$pass->id}/charge-requests", ['route_id' => $route->id])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'phone_missing');
    }

    public function test_inbound_sms_with_the_pin_charges_the_pass(): void
    {
        [$pass, $passenger] = $this->createPass(20, '4321', '0244000111', Pass::APPROVAL_CUSTOMER_PHONE);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);
        $sms = $this->fakeSms();

        Sanctum::actingAs($driver);
        $this->postJson("/api/passes/{$pass->id}/charge-requests", ['route_id' => $route->id])->assertCreated();

        $this->withHeader('X-Sms-Secret', 'test-secret')
            ->postJson('/api/sms/inbound', [
                'from' => '024 400 0111',
                'text' => '4321',
            ])
            ->assertOk()
            ->assertJsonPath('status', PassChargeRequest::STATUS_COMPLETED);

        $chargeRequest = PassChargeRequest::firstOrFail();
        $this->assertSame(PassChargeRequest::STATUS_COMPLETED, $chargeRequest->status);
        $this->assertNotNull($chargeRequest->ride_id);

        $ride = Ride::where('pass_id', $pass->id)->firstOrFail();
        $this->assertSame($passenger->id, $ride->passenger_id);
        $this->assertSame($driver->id, $ride->driver_id);
        $this->assertSame(3.5, (float) $ride->fare_amount);
        $this->assertTrue($ride->payment_metadata['remote_approval']);
        $this->assertSame('sms', $ride->payment_metadata['approval_channel']);
        $this->assertSame($chargeRequest->id, $ride->payment_metadata['charge_request_id']);
        $this->assertSame($chargeRequest->ride_id, $ride->id);

        $this->assertSame(16.5, (float) Wallet::where('user_id', $passenger->id)->value('balance'));
        $this->assertSame(3.5, (float) Wallet::where('user_id', $driver->id)->value('balance'));

        $this->assertStringContainsString('Approved. GHS 3.50 charged.', $sms->sent[array_key_last($sms->sent)]['message']);
        $this->assertStringContainsString('GHS 16.50 left on the pass.', $sms->sent[array_key_last($sms->sent)]['message']);
    }

    public function test_wrong_pin_keeps_the_request_pending(): void
    {
        [$pass] = $this->createPass(20, '4321', '0244000111', Pass::APPROVAL_CUSTOMER_PHONE);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);
        $sms = $this->fakeSms();

        Sanctum::actingAs($driver);
        $this->postJson("/api/passes/{$pass->id}/charge-requests", ['route_id' => $route->id])->assertCreated();

        $this->withHeader('X-Sms-Secret', 'test-secret')
            ->postJson('/api/sms/inbound', ['from' => '0244000111', 'text' => '1111'])
            ->assertOk()
            ->assertJsonPath('status', PassChargeRequest::STATUS_PENDING);

        $this->assertSame(1, $pass->fresh()->pin_attempts);
        $this->assertDatabaseMissing('rides', ['pass_id' => $pass->id]);
        $this->assertStringContainsString('Wrong PIN. 2 tries left.', $sms->sent[array_key_last($sms->sent)]['message']);
    }

    public function test_customer_can_decline_by_sms(): void
    {
        [$pass] = $this->createPass(20, '4321', '0244000111', Pass::APPROVAL_CUSTOMER_PHONE);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);
        $sms = $this->fakeSms();

        Sanctum::actingAs($driver);
        $this->postJson("/api/passes/{$pass->id}/charge-requests", ['route_id' => $route->id])->assertCreated();

        $this->withHeader('X-Sms-Secret', 'test-secret')
            ->postJson('/api/sms/inbound', ['from' => '0244000111', 'text' => '0'])
            ->assertOk()
            ->assertJsonPath('status', PassChargeRequest::STATUS_DECLINED);

        $this->assertDatabaseMissing('rides', ['pass_id' => $pass->id]);
        $this->assertSame(20.0, (float) Wallet::where('user_id', $pass->user_id)->value('balance'));
        $this->assertStringContainsString('Trip cancelled. No money was taken.', $sms->sent[array_key_last($sms->sent)]['message']);
    }

    public function test_expired_request_cannot_be_approved(): void
    {
        [$pass] = $this->createPass(20, '4321', '0244000111', Pass::APPROVAL_CUSTOMER_PHONE);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);
        $sms = $this->fakeSms();

        Sanctum::actingAs($driver);
        $this->postJson("/api/passes/{$pass->id}/charge-requests", ['route_id' => $route->id])->assertCreated();

        $this->travel(4)->minutes();

        $this->withHeader('X-Sms-Secret', 'test-secret')
            ->postJson('/api/sms/inbound', ['from' => '0244000111', 'text' => '4321'])
            ->assertOk();

        $this->assertSame(PassChargeRequest::STATUS_EXPIRED, PassChargeRequest::firstOrFail()->status);
        $this->assertDatabaseMissing('rides', ['pass_id' => $pass->id]);
        $this->assertStringContainsString('No trip is waiting for approval.', $sms->sent[array_key_last($sms->sent)]['message']);
    }

    public function test_ussd_prompts_then_approves(): void
    {
        [$pass] = $this->createPass(20, '4321', '0244000111', Pass::APPROVAL_CUSTOMER_PHONE);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);
        $this->fakeSms();

        Sanctum::actingAs($driver);
        $this->postJson("/api/passes/{$pass->id}/charge-requests", ['route_id' => $route->id])->assertCreated();

        $this->withHeader('X-Sms-Secret', 'test-secret')
            ->postJson('/api/ussd', ['msisdn' => '0244000111', 'text' => ''])
            ->assertOk()
            ->assertJsonPath('continue', true)
            ->assertJsonPath('status', PassChargeRequest::STATUS_PENDING);

        $this->withHeader('X-Sms-Secret', 'test-secret')
            ->postJson('/api/ussd', ['msisdn' => '0244000111', 'text' => '4321'])
            ->assertOk()
            ->assertJsonPath('continue', false)
            ->assertJsonPath('status', PassChargeRequest::STATUS_COMPLETED);

        $this->assertSame(1, Ride::where('pass_id', $pass->id)->count());
        $this->assertSame('ussd', Ride::firstOrFail()->payment_metadata['approval_channel']);
    }

    public function test_driver_can_poll_and_cancel_a_request(): void
    {
        [$pass] = $this->createPass(20, '4321', '0244000111', Pass::APPROVAL_CUSTOMER_PHONE);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);
        $this->fakeSms();

        Sanctum::actingAs($driver);
        $id = $this->postJson("/api/passes/{$pass->id}/charge-requests", ['route_id' => $route->id])
            ->assertCreated()
            ->json('request.id');

        $this->getJson("/api/passes/charge-requests/{$id}")
            ->assertOk()
            ->assertJsonPath('request.status', PassChargeRequest::STATUS_PENDING);

        $this->postJson("/api/passes/charge-requests/{$id}/cancel")
            ->assertOk()
            ->assertJsonPath('request.status', PassChargeRequest::STATUS_CANCELLED);

        $this->assertSame(PassChargeRequest::STATUS_CANCELLED, PassChargeRequest::firstOrFail()->status);
    }

    public function test_completed_request_cannot_be_cancelled_or_marked_expired(): void
    {
        [$pass] = $this->createPass(20, '4321', '0244000111', Pass::APPROVAL_CUSTOMER_PHONE);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);
        $this->fakeSms();

        Sanctum::actingAs($driver);
        $id = $this->postJson("/api/passes/{$pass->id}/charge-requests", ['route_id' => $route->id])
            ->assertCreated()
            ->json('request.id');

        $this->withHeader('X-Sms-Secret', 'test-secret')
            ->postJson('/api/sms/inbound', ['from' => '0244000111', 'text' => '4321'])
            ->assertOk()
            ->assertJsonPath('status', PassChargeRequest::STATUS_COMPLETED);

        $this->travel(4)->minutes();

        $this->getJson("/api/passes/charge-requests/{$id}")
            ->assertOk()
            ->assertJsonPath('request.status', PassChargeRequest::STATUS_COMPLETED);

        $this->postJson("/api/passes/charge-requests/{$id}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('code', 'request_completed');

        $this->assertSame(PassChargeRequest::STATUS_COMPLETED, PassChargeRequest::firstOrFail()->status);
        $this->assertSame(1, Ride::where('pass_id', $pass->id)->count());
    }

    public function test_only_the_requesting_driver_can_view_or_cancel(): void
    {
        [$pass] = $this->createPass(20, '4321', '0244000111', Pass::APPROVAL_CUSTOMER_PHONE);
        $driver = $this->createDriver();
        $other = $this->createDriver();
        $route = $this->createRoute(3.5);
        $this->fakeSms();

        Sanctum::actingAs($driver);
        $id = $this->postJson("/api/passes/{$pass->id}/charge-requests", ['route_id' => $route->id])
            ->assertCreated()
            ->json('request.id');

        Sanctum::actingAs($other);
        $this->getJson("/api/passes/charge-requests/{$id}")->assertForbidden();
        $this->postJson("/api/passes/charge-requests/{$id}/cancel")->assertForbidden();
    }

    public function test_new_request_replaces_the_older_pending_one(): void
    {
        [$pass] = $this->createPass(20, '4321', '0244000111', Pass::APPROVAL_CUSTOMER_PHONE);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);
        $this->fakeSms();

        Sanctum::actingAs($driver);
        $this->postJson("/api/passes/{$pass->id}/charge-requests", ['route_id' => $route->id])->assertCreated();
        $this->postJson("/api/passes/{$pass->id}/charge-requests", ['route_id' => $route->id])->assertCreated();

        $requests = PassChargeRequest::orderBy('id')->get();

        $this->assertSame(PassChargeRequest::STATUS_CANCELLED, $requests[0]->status);
        $this->assertSame(PassChargeRequest::STATUS_PENDING, $requests[1]->status);
    }

    public function test_webhooks_require_the_shared_secret(): void
    {
        $this->postJson('/api/sms/inbound', ['from' => '0244000111', 'text' => '4321'])
            ->assertForbidden();

        config(['sms.webhook_secret' => '']);

        $this->postJson('/api/sms/inbound', ['from' => '0244000111', 'text' => '4321'])
            ->assertStatus(503);
    }

    public function test_customer_phone_pass_still_accepts_a_direct_pin_charge(): void
    {
        [$pass] = $this->createPass(20, '4321', '0244000111', Pass::APPROVAL_CUSTOMER_PHONE);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);

        Sanctum::actingAs($driver);

        $this->postJson("/api/passes/{$pass->id}/charge", [
            'route_id' => $route->id,
            'pin' => '4321',
        ])->assertCreated();

        $this->assertSame(1, Ride::where('pass_id', $pass->id)->count());
    }

    private function createPass(
        float $balance,
        ?string $pin = null,
        ?string $phone = null,
        string $channel = Pass::APPROVAL_DEVICE_PIN
    ): array {
        $passenger = User::factory()->create([
            'role' => 'passenger',
            'is_assisted' => true,
            'phone' => $phone,
        ]);

        $pass = Pass::create([
            'user_id' => $passenger->id,
            'code' => Pass::generateCode(),
            'status' => Pass::STATUS_ACTIVE,
            'approval_channel' => $channel,
        ]);

        if ($pin !== null) {
            $pass->setPin($pin);
        }

        Wallet::where('user_id', $passenger->id)->update(['balance' => $balance]);

        return [$pass, $passenger];
    }

    private function createDriver(): User
    {
        $driver = User::factory()->create(['role' => 'driver']);

        Vehicle::create([
            'driver_id' => $driver->id,
            'make' => 'Toyota',
            'model' => 'Hiace',
            'year' => '2018',
            'license_plate' => 'GT-'.random_int(1000, 9999).'-18',
            'color' => 'White',
            'capacity' => 15,
            'vehicle_type' => 'minivan',
            'is_active' => true,
        ]);

        return $driver;
    }

    private function createRoute(float $fare): Route
    {
        $origin = BusStop::create(['name' => 'Circle', 'latitude' => 5.5700, 'longitude' => -0.2050]);
        $destination = BusStop::create(['name' => 'Kaneshie', 'latitude' => 5.5570, 'longitude' => -0.2330]);

        $route = Route::create([
            'origin_stop_id' => $origin->id,
            'destination_stop_id' => $destination->id,
            'route_name' => 'Circle to Kaneshie',
        ]);

        Fare::create([
            'route_id' => $route->id,
            'amount' => $fare,
            'fare_type' => 'flat',
            'is_active' => true,
        ]);

        return $route;
    }

    private function fakeSms(): object
    {
        $fake = new class implements SmsGateway
        {
            public array $sent = [];

            public function send(string $to, string $message): void
            {
                $this->sent[] = ['to' => $to, 'message' => $message];
            }
        };

        $this->app->instance(SmsGateway::class, $fake);

        return $fake;
    }
}
