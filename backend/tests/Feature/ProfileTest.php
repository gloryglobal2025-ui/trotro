<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_their_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'passenger',
            'name' => 'Old Name',
            'phone' => '0240000000',
        ]);

        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/profile', [
            'name' => 'New Name',
            'email' => 'new.name@example.com',
            'phone' => '0201111111',
        ])
            ->assertOk()
            ->assertJsonPath('user.name', 'New Name')
            ->assertJsonPath('user.email', 'new.name@example.com')
            ->assertJsonPath('user.phone', '0201111111');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new.name@example.com',
            'phone' => '0201111111',
        ]);
    }

    public function test_user_can_keep_their_existing_email(): void
    {
        $user = User::factory()->create(['role' => 'driver']);

        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ])->assertOk()->assertJsonPath('user.email', $user->email);
    }

    public function test_profile_update_rejects_an_email_taken_by_someone_else(): void
    {
        $other = User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['role' => 'passenger']);

        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/profile', [
            'name' => $user->name,
            'email' => $other->email,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_profile_update_ignores_role_and_password_fields(): void
    {
        $user = User::factory()->create(['role' => 'passenger']);
        $originalPassword = $user->password;

        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/profile', [
            'name' => 'Updated Passenger',
            'email' => $user->email,
            'role' => 'admin',
            'password' => 'super-secret-password',
        ])->assertOk()->assertJsonPath('user.role', 'passenger');

        $user->refresh();
        $this->assertSame('passenger', $user->role);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_profile_update_requires_auth_and_valid_fields(): void
    {
        $this->patchJson('/api/auth/profile', [
            'name' => 'Nobody',
            'email' => 'nobody@example.com',
        ])->assertUnauthorized();

        $user = User::factory()->create(['role' => 'passenger']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/profile', [
            'name' => '',
            'email' => 'not-an-email',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }
}
