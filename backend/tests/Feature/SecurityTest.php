<?php

namespace Tests\Feature;

use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create(['role' => 'passenger']);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_the_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_guests_cannot_write_catalogue_data(): void
    {
        $this->postJson('/api/bus-stops', ['name' => 'Test Stop'])->assertUnauthorized();
    }

    public function test_non_admins_cannot_write_catalogue_data(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'passenger']));

        $this->postJson('/api/bus-stops', ['name' => 'Test Stop'])->assertForbidden();
    }

    public function test_admins_can_write_catalogue_data(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/bus-stops', ['name' => 'Test Stop'])->assertCreated();
    }

    public function test_authenticated_requests_no_longer_recurse(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'passenger']));

        $this->getJson('/api/auth/me')->assertOk();
    }

    public function test_rides_are_private_to_participants(): void
    {
        $owner = User::factory()->create(['role' => 'passenger']);
        $other = User::factory()->create(['role' => 'passenger']);

        $ride = $this->createRide($owner);

        Sanctum::actingAs($other);

        $this->getJson("/api/rides/{$ride->id}")->assertForbidden();
        $this->putJson("/api/rides/{$ride->id}", ['status' => 'cancelled'])->assertForbidden();
    }

    public function test_vehicles_can_only_be_modified_by_their_owner(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $other = User::factory()->create(['role' => 'driver']);

        $vehicle = \App\Models\Vehicle::create([
            'driver_id' => $driver->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2020,
            'license_plate' => 'GW-1111-20',
            'color' => 'White',
            'capacity' => 4,
            'vehicle_type' => 'sedan',
            'is_active' => false,
        ]);

        Sanctum::actingAs($other);

        $this->putJson("/api/vehicles/{$vehicle->id}", ['color' => 'Black'])->assertForbidden();
        $this->deleteJson("/api/vehicles/{$vehicle->id}")->assertForbidden();
    }

    public function test_cash_payment_is_recorded_after_completion(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $ride = $this->createRide($passenger, ['status' => 'completed']);

        Sanctum::actingAs($passenger);

        $this->postJson("/api/payments/{$ride->id}/process", ['payment_method' => 'cash'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);
    }

    public function test_online_payment_methods_are_not_accepted(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $ride = $this->createRide($passenger, ['status' => 'completed']);

        Sanctum::actingAs($passenger);

        $this->postJson("/api/payments/{$ride->id}/process", ['payment_method' => 'card'])
            ->assertStatus(422);

        $this->postJson("/api/payments/{$ride->id}/process", ['payment_method' => 'momo'])
            ->assertStatus(422);
    }

    public function test_another_passenger_cannot_pay_for_a_ride(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $other = User::factory()->create(['role' => 'passenger']);
        $ride = $this->createRide($passenger, ['status' => 'completed']);

        Sanctum::actingAs($other);

        $this->postJson("/api/payments/{$ride->id}/process", ['payment_method' => 'cash'])
            ->assertForbidden();
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
