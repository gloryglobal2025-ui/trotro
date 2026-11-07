<?php

namespace Tests\Feature;

use App\Models\Ride;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RideBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_passenger_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test Passenger',
            'email' => 'passenger@example.com',
            'password' => 'password123',
            'phone' => '+233201234567',
            'role' => 'passenger',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'passenger@example.com',
            'role' => 'passenger',
        ]);
    }

    public function test_driver_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test Driver',
            'email' => 'driver@example.com',
            'password' => 'password123',
            'phone' => '+233207654321',
            'role' => 'driver',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'driver@example.com',
            'role' => 'driver',
        ]);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role' => 'passenger',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user',
            ]);
    }

    public function test_passenger_can_request_ride(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $token = $passenger->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/rides', [
            'origin_latitude' => 5.6037,
            'origin_longitude' => -0.1870,
            'origin_address' => 'Circle, Accra',
            'destination_latitude' => 5.5716,
            'destination_longitude' => -0.0254,
            'destination_address' => 'Makola, Accra',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'passenger_id',
                'status',
                'origin_address',
                'destination_address',
                'fare_amount',
            ]);

        $this->assertDatabaseHas('rides', [
            'passenger_id' => $passenger->id,
            'status' => 'pending',
        ]);
    }

    public function test_driver_can_register_vehicle(): void
    {
        Storage::fake('public');

        $driver = User::factory()->create(['role' => 'driver']);
        $token = $driver->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)->post('/api/vehicles', [
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2020,
            'license_plate' => 'GW-1234-20',
            'color' => 'White',
            'capacity' => 4,
            'vehicle_type' => 'sedan',
            'vehicle_image' => UploadedFile::fake()->image('vehicle.jpg'),
            'registration_image' => UploadedFile::fake()->image('registration.jpg'),
            'roadworthy_image' => UploadedFile::fake()->image('roadworthy.jpg'),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'driver_id',
                'make',
                'model',
                'license_plate',
            ])
            ->assertJsonPath('request_status', 'pending')
            ->assertJsonPath('is_active', false);

        $this->assertDatabaseHas('vehicles', [
            'driver_id' => $driver->id,
            'license_plate' => 'GW-1234-20',
            'request_status' => 'pending',
            'is_active' => false,
        ]);
    }

    public function test_driver_can_see_available_rides(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $passenger = User::factory()->create(['role' => 'passenger']);
        $token = $driver->createToken('test-token')->plainTextToken;

        $ride = Ride::create([
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

        $response = $this->withToken($token)->getJson('/api/rides/available');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_passenger_can_get_their_rides(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);
        $token = $passenger->createToken('test-token')->plainTextToken;

        $ride = Ride::create([
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

        $response = $this->withToken($token)->getJson('/api/rides');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_driver_can_accept_a_pending_ride(): void
    {
        $driver = User::factory()->create([
            'role' => 'driver',
            'verification_status' => 'approved',
        ]);
        $passenger = User::factory()->create(['role' => 'passenger']);
        $token = $driver->createToken('test-token')->plainTextToken;

        $vehicle = Vehicle::create([
            'driver_id' => $driver->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2020,
            'license_plate' => 'GW-1234-20',
            'color' => 'White',
            'capacity' => 4,
            'vehicle_type' => 'sedan',
            'is_active' => true,
            'request_status' => 'approved',
        ]);

        $ride = Ride::create([
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

        $response = $this->withToken($token)->putJson("/api/rides/{$ride->id}", [
            'status' => 'accepted',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('driver_id', $driver->id)
            ->assertJsonPath('vehicle_id', $vehicle->id);

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'accepted',
        ]);
    }
}
