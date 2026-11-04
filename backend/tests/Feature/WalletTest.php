<?php

namespace Tests\Feature;

use App\Models\Ride;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_wallet(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Wallet User',
            'email' => 'wallet@example.com',
            'password' => 'password123',
            'phone' => '+233201234567',
            'role' => 'passenger',
        ])->assertCreated();

        $user = User::where('email', 'wallet@example.com')->firstOrFail();

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 0,
            'bonus_balance' => 0,
        ]);
    }

    public function test_passenger_can_pay_a_completed_ride_from_wallet(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $driver = User::factory()->create(['role' => 'driver']);
        $this->fund($passenger, 100);

        $ride = $this->createRide($passenger, [
            'driver_id' => $driver->id,
            'status' => 'completed',
            'fare_amount' => 20,
        ]);

        Sanctum::actingAs($passenger);

        $this->postJson("/api/payments/{$ride->id}/process", [
            'payment_method' => 'wallet',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'payment_status' => 'paid',
            'payment_method' => 'wallet',
        ]);

        $this->assertSame(80.0, (float) Wallet::where('user_id', $passenger->id)->value('balance'));
        $this->assertSame(20.0, (float) Wallet::where('user_id', $driver->id)->value('balance'));

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $passenger->id,
            'type' => 'ride_payment',
            'amount' => -20,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $driver->id,
            'type' => 'ride_earning',
            'amount' => 20,
        ]);
    }

    public function test_wallet_payment_is_rejected_when_balance_is_insufficient(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $driver = User::factory()->create(['role' => 'driver']);
        $this->fund($passenger, 5);

        $ride = $this->createRide($passenger, [
            'driver_id' => $driver->id,
            'status' => 'completed',
            'fare_amount' => 20,
        ]);

        Sanctum::actingAs($passenger);

        $this->postJson("/api/payments/{$ride->id}/process", [
            'payment_method' => 'wallet',
        ])->assertStatus(422);

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'payment_status' => 'pending',
        ]);

        $this->assertSame(5.0, (float) Wallet::where('user_id', $passenger->id)->value('balance'));
        $this->assertSame(0.0, (float) Wallet::where('user_id', $driver->id)->value('balance'));
    }

    public function test_cash_payment_does_not_credit_the_driver_wallet(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $driver = User::factory()->create(['role' => 'driver']);

        $ride = $this->createRide($passenger, [
            'driver_id' => $driver->id,
            'status' => 'completed',
            'fare_amount' => 20,
        ]);

        Sanctum::actingAs($passenger);

        $this->postJson("/api/payments/{$ride->id}/process", [
            'payment_method' => 'cash',
        ])->assertOk();

        $this->assertSame(0.0, (float) Wallet::where('user_id', $driver->id)->value('balance'));
    }

    public function test_admin_approval_credits_a_deposit_request(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($passenger);

        $response = $this->postJson('/api/wallet/deposits', [
            'amount' => 50,
            'method' => 'momo',
            'reference' => 'MP-123456',
        ])->assertCreated();

        $depositId = $response->json('deposit.id');

        $this->actingAs($admin)
            ->post("/admin/users/deposits/{$depositId}/approve")
            ->assertRedirect();

        $this->assertSame(50.0, (float) Wallet::where('user_id', $passenger->id)->value('balance'));

        $this->assertDatabaseHas('wallet_deposits', [
            'id' => $depositId,
            'status' => 'approved',
            'processed_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $passenger->id,
            'type' => 'deposit',
            'amount' => 50,
        ]);
    }

    public function test_withdrawal_request_holds_funds_and_rejection_returns_them(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->fund($passenger, 100);

        Sanctum::actingAs($passenger);

        $response = $this->postJson('/api/wallet/withdrawals', [
            'amount' => 40,
            'method' => 'momo',
            'mobile_number' => '0244000000',
        ])->assertCreated();

        $withdrawalId = $response->json('withdrawal.id');

        $wallet = Wallet::where('user_id', $passenger->id)->firstOrFail();
        $this->assertSame(60.0, (float) $wallet->balance);
        $this->assertSame(40.0, (float) $wallet->pending_balance);

        $this->actingAs($admin)
            ->post("/admin/users/withdrawals/{$withdrawalId}/reject", ['notes' => 'Wrong number'])
            ->assertRedirect();

        $wallet->refresh();
        $this->assertSame(100.0, (float) $wallet->balance);
        $this->assertSame(0.0, (float) $wallet->pending_balance);

        $this->assertDatabaseHas('withdrawals', [
            'id' => $withdrawalId,
            'status' => 'rejected',
        ]);
    }

    public function test_approving_a_withdrawal_settles_the_held_funds(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->fund($passenger, 100);

        Sanctum::actingAs($passenger);

        $response = $this->postJson('/api/wallet/withdrawals', [
            'amount' => 40,
            'method' => 'bank',
            'bank_name' => 'GCB',
            'account_name' => 'Wallet User',
            'account_number' => '1234567890',
        ])->assertCreated();

        $withdrawalId = $response->json('withdrawal.id');

        $this->actingAs($admin)
            ->post("/admin/users/withdrawals/{$withdrawalId}/approve")
            ->assertRedirect();

        $wallet = Wallet::where('user_id', $passenger->id)->firstOrFail();
        $this->assertSame(60.0, (float) $wallet->balance);
        $this->assertSame(0.0, (float) $wallet->pending_balance);

        $this->assertDatabaseHas('withdrawals', [
            'id' => $withdrawalId,
            'status' => 'approved',
            'processed_by' => $admin->id,
        ]);
    }

    public function test_wallet_refund_returns_funds_and_reverses_driver_credit(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $driver = User::factory()->create(['role' => 'driver']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->fund($passenger, 100);

        $ride = $this->createRide($passenger, [
            'driver_id' => $driver->id,
            'status' => 'completed',
            'fare_amount' => 20,
        ]);

        Sanctum::actingAs($passenger);

        $this->postJson("/api/payments/{$ride->id}/process", [
            'payment_method' => 'wallet',
        ])->assertOk();

        Sanctum::actingAs($admin);

        $this->postJson("/api/payments/{$ride->id}/refund", [
            'reason' => 'Ride disputed',
        ])->assertOk();

        $this->assertSame(100.0, (float) Wallet::where('user_id', $passenger->id)->value('balance'));
        $this->assertSame(0.0, (float) Wallet::where('user_id', $driver->id)->value('balance'));

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'payment_status' => 'refunded',
        ]);
    }

    public function test_withdrawal_request_is_rejected_when_balance_is_insufficient(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $this->fund($passenger, 10);

        Sanctum::actingAs($passenger);

        $this->postJson('/api/wallet/withdrawals', [
            'amount' => 40,
            'method' => 'momo',
            'mobile_number' => '0244000000',
        ])->assertStatus(422);

        $this->assertDatabaseCount('withdrawals', 0);

        $wallet = Wallet::where('user_id', $passenger->id)->firstOrFail();
        $this->assertSame(10.0, (float) $wallet->balance);
        $this->assertSame(0.0, (float) $wallet->pending_balance);
    }

    private function fund(User $user, float $amount): void
    {
        Wallet::where('user_id', $user->id)->update(['balance' => $amount]);
    }

    private function createRide(User $passenger, array $overrides = []): Ride
    {
        return Ride::create(array_merge([
            'passenger_id' => $passenger->id,
            'origin_latitude' => 5.6037,
            'origin_longitude' => -0.1870,
            'origin_address' => 'Circle, Accra',
            'destination_latitude' => 5.5716,
            'destination_longitude' => -0.0254,
            'destination_address' => 'Makola, Accra',
            'status' => 'pending',
            'payment_status' => 'pending',
        ], $overrides));
    }
}
