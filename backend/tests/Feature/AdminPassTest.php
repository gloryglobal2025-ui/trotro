<?php

namespace Tests\Feature;

use App\Models\Pass;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPassTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_pass_with_a_loaded_wallet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/passes', [
            'name' => 'Ama Mensah',
            'phone' => '0244000111',
            'initial_balance' => 25,
            'notes' => 'Cannot read; daughter collects the card.',
        ]);

        $pass = Pass::with('user.wallet')->firstOrFail();

        $response->assertRedirect(route('admin.passes.show', $pass));

        $this->assertMatchesRegularExpression('/^TRT-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $pass->code);
        $this->assertTrue($pass->user->is_assisted);
        $this->assertSame('Ama Mensah', $pass->user->name);
        $this->assertSame($admin->id, $pass->issued_by);
        $this->assertSame(25.0, (float) $pass->user->wallet->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $pass->user_id,
            'type' => 'pass_load',
            'amount' => 25,
        ]);
    }

    public function test_admin_sees_the_printable_card_and_can_top_up(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$pass] = $this->makePass(10);

        $this->actingAs($admin)
            ->get(route('admin.passes.show', $pass))
            ->assertOk()
            ->assertSee($pass->code)
            ->assertSee('Print card')
            ->assertSee('pass-qr')
            ->assertSee('Customer approval');

        $this->actingAs($admin)
            ->post(route('admin.passes.topup', $pass), ['amount' => 15])
            ->assertRedirect(route('admin.passes.show', $pass));

        $this->assertSame(25.0, (float) Wallet::where('user_id', $pass->user_id)->value('balance'));
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $pass->user_id,
            'type' => 'pass_topup',
            'amount' => 15,
        ]);
    }

    public function test_admin_can_suspend_and_reactivate_a_pass(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$pass] = $this->makePass(10);

        $this->actingAs($admin)
            ->post(route('admin.passes.status', $pass), ['status' => 'suspended'])
            ->assertRedirect();

        $this->assertSame('suspended', $pass->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.passes.status', $pass), ['status' => 'active'])
            ->assertRedirect();

        $this->assertSame('active', $pass->fresh()->status);
    }

    public function test_regenerating_changes_the_code_and_invalidates_the_old_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$pass] = $this->makePass(10);
        $oldCode = $pass->code;

        $this->actingAs($admin)
            ->post(route('admin.passes.regenerate', $pass))
            ->assertRedirect()
            ->assertSessionHas('success');

        $pass->refresh();

        $this->assertNotSame($oldCode, $pass->code);
        $this->assertSame(0, Pass::where('code', $oldCode)->count());
    }

    public function test_admin_can_open_the_pass_registration_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.passes.create'))
            ->assertOk()
            ->assertSee('PIN (4 numbers)')
            ->assertSee('Customer approval')
            ->assertSee('own phone');
    }

    public function test_admin_can_register_a_pass_with_a_customer_pin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/passes', [
            'name' => 'Yaa Asantewaa',
            'phone' => '0244000222',
            'initial_balance' => 10,
            'pin' => '4321',
            'pin_confirmation' => '4321',
        ])->assertRedirect();

        $pass = Pass::firstOrFail();

        $this->assertTrue($pass->requiresPin());
        $this->assertTrue(Hash::check('4321', $pass->pin_hash));
        $this->assertFalse(Hash::check('1234', $pass->pin_hash));
        $this->assertNotNull($pass->pin_set_at);
    }

    public function test_pin_must_be_four_digits_and_confirmed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/passes', [
            'name' => 'Bad Pin',
            'pin' => '12a4',
            'pin_confirmation' => '12a4',
        ])->assertSessionHasErrors('pin');

        $this->actingAs($admin)->post('/admin/passes', [
            'name' => 'Mismatch',
            'pin' => '4321',
            'pin_confirmation' => '1111',
        ])->assertSessionHasErrors('pin');

        $this->assertSame(0, Pass::count());
    }

    public function test_admin_can_replace_and_remove_a_pin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$pass] = $this->makePass(10);

        $this->actingAs($admin)
            ->post(route('admin.passes.pin', $pass), [
                'pin' => '1111',
                'pin_confirmation' => '1111',
            ])
            ->assertRedirect(route('admin.passes.show', $pass));

        $this->assertTrue(Hash::check('1111', $pass->fresh()->pin_hash));

        $this->actingAs($admin)
            ->post(route('admin.passes.pin', $pass), [
                'pin' => '9876',
                'pin_confirmation' => '9876',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('9876', $pass->fresh()->pin_hash));
        $this->assertFalse(Hash::check('1111', $pass->fresh()->pin_hash));

        $this->actingAs($admin)
            ->post(route('admin.passes.pin.remove', $pass))
            ->assertRedirect();

        $this->assertFalse($pass->fresh()->requiresPin());
        $this->assertNull($pass->fresh()->pin_hash);
    }

    public function test_admin_can_unlock_a_pin_locked_pass(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$pass] = $this->makePass(10);

        $pass->setPin('4321');
        $pass->forceFill([
            'pin_attempts' => 1,
            'pin_locked_until' => now()->addMinutes(10),
        ])->save();

        $this->assertTrue($pass->fresh()->isPinLocked());

        $this->actingAs($admin)
            ->post(route('admin.passes.pin.unlock', $pass))
            ->assertRedirect()
            ->assertSessionHas('success');

        $pass->refresh();

        $this->assertFalse($pass->isPinLocked());
        $this->assertSame(0, $pass->pin_attempts);
    }

    public function test_admin_can_register_a_pass_with_customer_phone_approval(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/passes', [
            'name' => 'Kofi Boateng',
            'phone' => '0244000333',
            'initial_balance' => 5,
            'approval_channel' => 'customer_phone',
            'pin' => '4321',
            'pin_confirmation' => '4321',
        ])->assertRedirect();

        $pass = Pass::firstOrFail();

        $this->assertSame(Pass::APPROVAL_CUSTOMER_PHONE, $pass->approval_channel);
        $this->assertTrue($pass->requiresPin());
        $this->assertSame('0244000333', $pass->user->phone);
    }

    public function test_phone_approval_requires_a_phone_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/passes', [
            'name' => 'No Phone',
            'approval_channel' => 'customer_phone',
            'pin' => '4321',
            'pin_confirmation' => '4321',
        ])->assertSessionHasErrors('phone');

        $this->actingAs($admin)->post('/admin/passes', [
            'name' => 'Bad Phone',
            'phone' => '12',
            'approval_channel' => 'customer_phone',
            'pin' => '4321',
            'pin_confirmation' => '4321',
        ])->assertSessionHasErrors('phone');

        $this->assertSame(0, Pass::count());
    }

    public function test_admin_can_switch_approval_mode_only_when_the_pass_is_ready(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$pass] = $this->makePass(10);

        $this->actingAs($admin)
            ->post(route('admin.passes.approval', $pass), ['approval_channel' => 'customer_phone'])
            ->assertSessionHasErrors('approval_channel');

        $this->assertSame(Pass::APPROVAL_DEVICE_PIN, $pass->fresh()->approval_channel);

        $pass->setPin('4321');
        $pass->user->update(['phone' => '0244000444']);

        $this->actingAs($admin)
            ->post(route('admin.passes.approval', $pass), ['approval_channel' => 'customer_phone'])
            ->assertRedirect(route('admin.passes.show', $pass));

        $this->assertSame(Pass::APPROVAL_CUSTOMER_PHONE, $pass->fresh()->approval_channel);

        $this->actingAs($admin)
            ->post(route('admin.passes.approval', $pass), ['approval_channel' => 'device_pin'])
            ->assertRedirect();

        $this->assertSame(Pass::APPROVAL_DEVICE_PIN, $pass->fresh()->approval_channel);
    }

    public function test_removing_the_pin_returns_approval_to_the_driver_phone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$pass] = $this->makePass(10);

        $pass->setPin('4321');
        $pass->update(['approval_channel' => Pass::APPROVAL_CUSTOMER_PHONE]);

        $this->actingAs($admin)
            ->post(route('admin.passes.pin.remove', $pass))
            ->assertRedirect();

        $pass->refresh();

        $this->assertFalse($pass->requiresPin());
        $this->assertSame(Pass::APPROVAL_DEVICE_PIN, $pass->approval_channel);
    }

    public function test_non_admins_cannot_open_pass_management(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);

        $this->actingAs($passenger)
            ->get(route('admin.passes.index'))
            ->assertForbidden();
    }

    private function makePass(float $balance): array
    {
        $user = User::factory()->create([
            'name' => 'Pass Holder',
            'role' => 'passenger',
            'is_assisted' => true,
        ]);

        $pass = Pass::create([
            'user_id' => $user->id,
            'code' => Pass::generateCode(),
            'status' => Pass::STATUS_ACTIVE,
        ]);

        Wallet::where('user_id', $user->id)->update(['balance' => $balance]);

        return [$pass, $user];
    }
}
