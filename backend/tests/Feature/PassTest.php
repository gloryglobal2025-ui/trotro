<?php

namespace Tests\Feature;

use App\Models\BusStop;
use App\Models\Fare;
use App\Models\Pass;
use App\Models\Ride;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PassTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_look_up_a_pass_with_a_messy_code(): void
    {
        [$pass] = $this->createPass(25);
        $driver = $this->createDriver();
        $this->createRoute(3.5);

        Sanctum::actingAs($driver);

        $messy = strtolower(str_replace('-', ' ', $pass->code));

        $this->getJson('/api/passes/lookup?code='.urlencode($messy))
            ->assertOk()
            ->assertJsonPath('pass.code', $pass->code)
            ->assertJsonPath('pass.balance', 25)
            ->assertJsonPath('pass.name', $pass->user->name)
            ->assertJsonPath('routes.0.fare', 3.5)
            ->assertJsonPath('routes.0.destination', 'Kaneshie');
    }

    public function test_passenger_cannot_look_up_or_charge_a_pass(): void
    {
        [$pass] = $this->createPass(25);
        $passenger = User::factory()->create(['role' => 'passenger']);
        $route = $this->createRoute(3.5);

        Sanctum::actingAs($passenger);

        $this->getJson('/api/passes/lookup?code='.$pass->code)->assertForbidden();
        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id])->assertForbidden();
    }

    public function test_unknown_code_returns_a_friendly_404(): void
    {
        $driver = $this->createDriver();
        Sanctum::actingAs($driver);

        $this->getJson('/api/passes/lookup?code=TRT-ZZZZ-9999')
            ->assertNotFound()
            ->assertJsonPath('message', 'No pass found for that code. Check the card and try again.');
    }

    public function test_driver_charge_debits_the_pass_and_credits_the_driver(): void
    {
        [$pass, $passenger] = $this->createPass(20);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);
        $fare = $route->fares()->firstOrFail();

        Sanctum::actingAs($driver);

        $this->postJson("/api/passes/{$pass->id}/charge", [
            'route_id' => $route->id,
            'fare_id' => $fare->id,
            'notes' => 'Kaneshie drop-off',
        ])
            ->assertCreated()
            ->assertJsonPath('pass.balance', 16.5)
            ->assertJsonPath('pass.ride_count', 1)
            ->assertJsonPath('driver_credited', true);

        $ride = Ride::where('pass_id', $pass->id)->firstOrFail();

        $this->assertSame('completed', $ride->status);
        $this->assertSame('paid', $ride->payment_status);
        $this->assertSame('wallet', $ride->payment_method);
        $this->assertSame($passenger->id, $ride->passenger_id);
        $this->assertSame($driver->id, $ride->driver_id);
        $this->assertSame(3.5, (float) $ride->fare_amount);
        $this->assertSame('Kaneshie', $ride->destination_address);
        $this->assertSame('pass', $ride->payment_metadata['method']);

        $this->assertSame(16.5, (float) Wallet::where('user_id', $passenger->id)->value('balance'));
        $this->assertSame(3.5, (float) Wallet::where('user_id', $driver->id)->value('balance'));

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $passenger->id,
            'type' => 'pass_ride',
            'amount' => -3.5,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $driver->id,
            'type' => 'ride_earning',
            'amount' => 3.5,
        ]);

        $pass->refresh();
        $this->assertNotNull($pass->last_used_at);
        $this->assertSame(1, $pass->ride_count);
    }

    public function test_charge_is_rejected_when_the_pass_has_no_money(): void
    {
        [$pass] = $this->createPass(2);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);

        Sanctum::actingAs($driver);

        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Not enough money on this pass. Load it before the trip.')
            ->assertJsonPath('balance', 2)
            ->assertJsonPath('fare', 3.5);

        $this->assertDatabaseMissing('rides', ['pass_id' => $pass->id]);
        $this->assertSame(2.0, (float) Wallet::where('user_id', $pass->user_id)->value('balance'));
    }

    public function test_suspended_pass_cannot_be_charged(): void
    {
        [$pass] = $this->createPass(20);
        $pass->update(['status' => Pass::STATUS_SUSPENDED]);

        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);

        Sanctum::actingAs($driver);

        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This pass is suspended. Ask an administrator to reactivate it.');

        $this->assertDatabaseMissing('rides', ['pass_id' => $pass->id]);
    }

    public function test_second_charge_within_the_window_is_blocked(): void
    {
        [$pass] = $this->createPass(20);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);

        Sanctum::actingAs($driver);

        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id])->assertCreated();
        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id])->assertStatus(409);

        $this->assertSame(1, Ride::where('pass_id', $pass->id)->count());
        $this->assertSame(16.5, (float) Wallet::where('user_id', $pass->user_id)->value('balance'));
    }

    public function test_admin_charge_does_not_credit_a_driver(): void
    {
        [$pass] = $this->createPass(20);
        $admin = User::factory()->create(['role' => 'admin']);
        $route = $this->createRoute(3.5);

        Sanctum::actingAs($admin);

        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id])
            ->assertCreated()
            ->assertJsonPath('driver_credited', false);

        $ride = Ride::where('pass_id', $pass->id)->firstOrFail();
        $this->assertNull($ride->driver_id);
    }

    public function test_route_without_an_active_fare_cannot_be_charged(): void
    {
        [$pass] = $this->createPass(20);
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);
        $route->fares()->update(['is_active' => false]);

        Sanctum::actingAs($driver);

        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This route has no active fare. Ask an administrator to set one.');
    }

    public function test_lookup_shows_pin_protection_without_leaking_the_hash(): void
    {
        [$pass] = $this->createPass(20, '4321');
        $driver = $this->createDriver();
        $this->createRoute(3.5);

        Sanctum::actingAs($driver);

        $response = $this->getJson('/api/passes/lookup?code='.$pass->code)
            ->assertOk()
            ->assertJsonPath('pass.pin_required', true)
            ->assertJsonPath('pass.pin_locked', false)
            ->assertJsonPath('pass.pin_attempts_left', 3);

        $this->assertArrayNotHasKey('pin_hash', $response->json('pass'));
    }

    public function test_pin_protected_pass_cannot_be_charged_without_the_customer_pin(): void
    {
        [$pass] = $this->createPass(20, '4321');
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);

        Sanctum::actingAs($driver);

        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id])
            ->assertStatus(428)
            ->assertJsonPath('code', 'pin_required');

        $this->assertDatabaseMissing('rides', ['pass_id' => $pass->id]);
        $this->assertSame(20.0, (float) Wallet::where('user_id', $pass->user_id)->value('balance'));
    }

    public function test_wrong_pin_counts_down_then_a_correct_pin_charges(): void
    {
        [$pass] = $this->createPass(20, '4321');
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);

        Sanctum::actingAs($driver);

        $this->postJson("/api/passes/{$pass->id}/charge", [
            'route_id' => $route->id,
            'pin' => '1111',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'pin_invalid')
            ->assertJsonPath('attempts_left', 2);

        $this->assertSame(1, $pass->fresh()->pin_attempts);
        $this->assertDatabaseMissing('rides', ['pass_id' => $pass->id]);

        $this->postJson("/api/passes/{$pass->id}/charge", [
            'route_id' => $route->id,
            'pin' => '4321',
        ])
            ->assertCreated()
            ->assertJsonPath('pass.balance', 16.5);

        $this->assertSame(0, $pass->fresh()->pin_attempts);
        $this->assertNull($pass->fresh()->pin_locked_until);
        $this->assertSame(1, Ride::where('pass_id', $pass->id)->count());
        $this->assertTrue(Ride::where('pass_id', $pass->id)->first()->payment_metadata['pin_approved']);
    }

    public function test_three_wrong_pins_lock_the_pass_until_unlocked(): void
    {
        [$pass] = $this->createPass(20, '4321');
        $driver = $this->createDriver();
        $route = $this->createRoute(3.5);

        Sanctum::actingAs($driver);

        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id, 'pin' => '1111'])->assertStatus(422);
        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id, 'pin' => '2222'])->assertStatus(422);
        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id, 'pin' => '3333'])
            ->assertStatus(423)
            ->assertJsonPath('code', 'pin_locked');

        $this->assertTrue($pass->fresh()->isPinLocked());

        // Even the right PIN is refused while locked.
        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id, 'pin' => '4321'])
            ->assertStatus(423);

        $pass->fresh()->unlockPin();

        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id, 'pin' => '4321'])
            ->assertCreated();
    }

    public function test_admin_can_charge_a_pin_pass_without_the_customer_pin(): void
    {
        [$pass] = $this->createPass(20, '4321');
        $admin = User::factory()->create(['role' => 'admin']);
        $route = $this->createRoute(3.5);

        Sanctum::actingAs($admin);

        $this->postJson("/api/passes/{$pass->id}/charge", ['route_id' => $route->id])
            ->assertCreated();

        $ride = Ride::where('pass_id', $pass->id)->firstOrFail();
        $this->assertFalse($ride->payment_metadata['pin_approved']);
    }

    private function createPass(float $balance, ?string $pin = null): array
    {
        $passenger = User::factory()->create(['role' => 'passenger', 'is_assisted' => true]);

        $pass = Pass::create([
            'user_id' => $passenger->id,
            'code' => Pass::generateCode(),
            'status' => Pass::STATUS_ACTIVE,
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
}
