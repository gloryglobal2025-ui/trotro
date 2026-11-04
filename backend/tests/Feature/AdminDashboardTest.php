<?php

namespace Tests\Feature;

use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_analytics_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $passenger = User::factory()->create(['role' => 'passenger', 'name' => 'Ama Boateng']);
        $driver = User::factory()->create(['role' => 'driver', 'name' => 'Kofi Mensah']);

        Ride::create([
            'passenger_id' => $passenger->id,
            'driver_id' => $driver->id,
            'origin_address' => 'Achimota',
            'destination_address' => 'Circle',
            'origin_latitude' => 5.621,
            'origin_longitude' => -0.231,
            'destination_latitude' => 5.571,
            'destination_longitude' => -0.207,
            'status' => 'completed',
            'fare_amount' => 6.5,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        Ride::create([
            'passenger_id' => $passenger->id,
            'origin_address' => 'Kaneshie',
            'destination_address' => 'Madina',
            'origin_latitude' => 5.566,
            'origin_longitude' => -0.234,
            'destination_latitude' => 5.683,
            'destination_longitude' => -0.166,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk()
            ->assertSee('Operations dashboard')
            ->assertSee('Rides &amp; revenue', false)
            ->assertSee('Ride status')
            ->assertSee('Top destinations')
            ->assertSee('Recent rides')
            ->assertSee('Circle')
            ->assertSee('Madina')
            ->assertSee('Ama Boateng')
            ->assertSee('Kofi Mensah')
            ->assertSee('GHS 6.50')
            ->assertSee('Waiting for driver');
    }

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
