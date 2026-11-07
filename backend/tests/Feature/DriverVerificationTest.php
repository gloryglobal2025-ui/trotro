<?php

namespace Tests\Feature;

use App\Models\Ride;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriverVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_submit_verification_documents(): void
    {
        Storage::fake('public');

        $driver = User::factory()->create(['role' => 'driver']);
        $token = $driver->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)->post('/api/auth/driver-verification', [
            'license_number' => 'DL-123456',
            'license_expiry' => now()->addYear()->toDateString(),
            'ghana_card_number' => 'GHA-123456789-0',
            'license_image' => UploadedFile::fake()->image('license.jpg'),
            'ghana_card_image' => UploadedFile::fake()->image('ghana-card.jpg'),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.verification_status', 'pending')
            ->assertJsonPath('user.license_number', 'DL-123456');

        $this->assertDatabaseHas('users', [
            'id' => $driver->id,
            'verification_status' => 'pending',
            'license_number' => 'DL-123456',
            'ghana_card_number' => 'GHA-123456789-0',
        ]);
    }

    public function test_passenger_cannot_submit_driver_verification(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $token = $passenger->createToken('test-token')->plainTextToken;

        $this->withToken($token)->post('/api/auth/driver-verification', [
            'license_number' => 'DL-123456',
            'license_expiry' => now()->addYear()->toDateString(),
            'ghana_card_number' => 'GHA-123456789-0',
        ])->assertStatus(403);
    }

    public function test_unverified_driver_cannot_accept_a_ride(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $passenger = User::factory()->create(['role' => 'passenger']);

        Vehicle::create([
            'driver_id' => $driver->id,
            'make' => 'Toyota',
            'model' => 'Hiace',
            'year' => 2018,
            'license_plate' => 'GT-1111-18',
            'color' => 'White',
            'capacity' => 15,
            'vehicle_type' => 'minivan',
            'is_active' => true,
            'request_status' => 'approved',
        ]);

        $ride = $this->makeRide($passenger);
        $token = $driver->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/rides/{$ride->id}", ['status' => 'accepted'])
            ->assertStatus(403)
            ->assertJsonPath(
                'message',
                'Your driver account is not verified yet. Submit your licence and Ghana Card for approval.'
            );
    }

    public function test_verified_driver_with_unapproved_vehicle_cannot_accept_a_ride(): void
    {
        $driver = User::factory()->create([
            'role' => 'driver',
            'verification_status' => 'approved',
        ]);
        $passenger = User::factory()->create(['role' => 'passenger']);

        Vehicle::create([
            'driver_id' => $driver->id,
            'make' => 'Toyota',
            'model' => 'Hiace',
            'year' => 2018,
            'license_plate' => 'GT-2222-18',
            'color' => 'White',
            'capacity' => 15,
            'vehicle_type' => 'minivan',
            'is_active' => false,
            'request_status' => 'pending',
        ]);

        $ride = $this->makeRide($passenger);
        $token = $driver->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/rides/{$ride->id}", ['status' => 'accepted'])
            ->assertStatus(422);
    }

    public function test_driver_cannot_activate_a_pending_vehicle(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $vehicle = Vehicle::create([
            'driver_id' => $driver->id,
            'make' => 'Toyota',
            'model' => 'Hiace',
            'year' => 2018,
            'license_plate' => 'GT-3333-18',
            'color' => 'White',
            'capacity' => 15,
            'vehicle_type' => 'minivan',
            'is_active' => false,
            'request_status' => 'pending',
        ]);

        $token = $driver->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/vehicles/{$vehicle->id}/availability", [
                'is_active' => true,
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'is_active' => false,
        ]);
    }

    public function test_driver_editing_a_rejected_vehicle_resubmits_it(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $vehicle = Vehicle::create([
            'driver_id' => $driver->id,
            'make' => 'Toyota',
            'model' => 'Hiace',
            'year' => 2018,
            'license_plate' => 'GT-4444-18',
            'color' => 'White',
            'capacity' => 15,
            'vehicle_type' => 'minivan',
            'is_active' => false,
            'request_status' => 'rejected',
            'rejection_reason' => 'Registration photo is blurry.',
        ]);

        $token = $driver->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/vehicles/{$vehicle->id}", [
                'make' => 'Toyota',
                'model' => 'Hiace',
                'year' => 2018,
                'license_plate' => 'GT-4444-18',
                'color' => 'White',
                'capacity' => 15,
                'vehicle_type' => 'minivan',
            ])
            ->assertStatus(200)
            ->assertJsonPath('request_status', 'pending');

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'request_status' => 'pending',
            'rejection_reason' => null,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_approve_driver_verification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $driver = User::factory()->create([
            'role' => 'driver',
            'verification_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.verification-requests.approve', $driver))
            ->assertRedirect(route('admin.users.verification-requests'));

        $this->assertDatabaseHas('users', [
            'id' => $driver->id,
            'verification_status' => 'approved',
            'verified_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reject_driver_verification_with_a_note(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $driver = User::factory()->create([
            'role' => 'driver',
            'verification_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.verification-requests.reject', $driver), [
                'verification_notes' => 'Licence photo is not readable.',
            ])
            ->assertRedirect(route('admin.users.verification-requests'));

        $this->assertDatabaseHas('users', [
            'id' => $driver->id,
            'verification_status' => 'rejected',
            'verification_notes' => 'Licence photo is not readable.',
        ]);
    }

    public function test_admin_approving_a_vehicle_request_activates_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $driver = User::factory()->create(['role' => 'driver']);
        $vehicle = Vehicle::create([
            'driver_id' => $driver->id,
            'make' => 'Toyota',
            'model' => 'Hiace',
            'year' => 2018,
            'license_plate' => 'GT-5555-18',
            'color' => 'White',
            'capacity' => 15,
            'vehicle_type' => 'minivan',
            'is_active' => false,
            'request_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.vehicles.new-requests.approve', $vehicle))
            ->assertRedirect(route('admin.vehicles.new-requests'));

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'request_status' => 'approved',
            'is_active' => true,
        ]);
    }

    private function makeRide(User $passenger): Ride
    {
        return Ride::create([
            'passenger_id' => $passenger->id,
            'origin_latitude' => 5.6037,
            'origin_longitude' => -0.1870,
            'origin_address' => 'Circle, Accra',
            'destination_latitude' => 5.5716,
            'destination_longitude' => -0.0254,
            'destination_address' => 'Makola, Accra',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }
}
