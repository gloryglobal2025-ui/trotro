<?php

namespace Tests\Feature;

use App\Models\BusStop;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RouteStopsTest extends TestCase
{
    use RefreshDatabase;

    private function makeStop(string $name): BusStop
    {
        return BusStop::create([
            'name' => $name,
            'latitude' => 5.6000,
            'longitude' => -0.2000,
            'area' => 'Accra',
        ]);
    }

    private function makeRoute(BusStop $origin, BusStop $destination): Route
    {
        return Route::create([
            'origin_stop_id' => $origin->id,
            'destination_stop_id' => $destination->id,
            'route_name' => $origin->name.' to '.$destination->name,
            'estimated_duration' => 40,
        ]);
    }

    private function saveStops(Route $route, array $stops): TestResponse
    {
        return $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put(route('admin.routes.stops.update', $route), ['stops' => $stops]);
    }

    public function test_admin_can_save_ordered_route_stops(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Amasaman');
        $c = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $c);

        $response = $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 3.50],
            ['bus_stop_id' => $c->id, 'fare_from_origin' => 10],
        ]);

        $response->assertRedirect(route('admin.routes.stops', $route));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseCount('route_stops', 3);
        $this->assertDatabaseHas('route_stops', [
            'route_id' => $route->id,
            'bus_stop_id' => $a->id,
            'sequence' => 1,
            'fare_from_origin' => 0,
        ]);
        $this->assertDatabaseHas('route_stops', [
            'route_id' => $route->id,
            'bus_stop_id' => $b->id,
            'sequence' => 2,
            'fare_from_origin' => 3.50,
        ]);
        $this->assertDatabaseHas('route_stops', [
            'route_id' => $route->id,
            'bus_stop_id' => $c->id,
            'sequence' => 3,
            'fare_from_origin' => 10,
        ]);
    }

    public function test_route_stops_must_start_at_the_route_origin(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Amasaman');
        $c = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $c);

        $response = $this->saveStops($route, [
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 2],
            ['bus_stop_id' => $c->id, 'fare_from_origin' => 10],
        ]);

        $response->assertSessionHasErrors('stops');
        $this->assertDatabaseCount('route_stops', 0);
    }

    public function test_route_stops_must_end_at_the_route_destination(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Amasaman');
        $c = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $c);

        $response = $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 10],
        ]);

        $response->assertSessionHasErrors('stops');
        $this->assertDatabaseCount('route_stops', 0);
    }

    public function test_route_stop_fares_cannot_decrease(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Amasaman');
        $c = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $c);

        $response = $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 7],
            ['bus_stop_id' => $c->id, 'fare_from_origin' => 4],
        ]);

        $response->assertSessionHasErrors('stops');
        $this->assertDatabaseCount('route_stops', 0);
    }

    public function test_route_stops_must_be_unique(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Amasaman');
        $c = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $c);

        $response = $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 5],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 10],
            ['bus_stop_id' => $c->id, 'fare_from_origin' => 10],
        ]);

        $response->assertSessionHasErrors('stops.1.bus_stop_id');
        $this->assertDatabaseCount('route_stops', 0);
    }

    public function test_saving_stops_replaces_previous_rows(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Amasaman');
        $c = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $c);

        $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $c->id, 'fare_from_origin' => 10],
        ])->assertSessionHasNoErrors();

        $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 3.50],
            ['bus_stop_id' => $c->id, 'fare_from_origin' => 10],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('route_stops', 3);
    }

    public function test_from_stop_lists_downstream_stops_with_segment_fares(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Amasaman');
        $c = $this->makeStop('Achimota');
        $d = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $d);

        $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 3.50],
            ['bus_stop_id' => $c->id, 'fare_from_origin' => 7],
            ['bus_stop_id' => $d->id, 'fare_from_origin' => 10],
        ])->assertSessionHasNoErrors();

        $response = $this->getJson('/api/routes/from-stop/'.$b->id);

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.route_id', $route->id)
            ->assertJsonPath('0.boarding_stop_id', $b->id)
            ->assertJsonPath('0.destination_name', 'Circle')
            ->assertJsonCount(2, '0.stops')
            ->assertJsonPath('0.stops.0.bus_stop_id', $c->id)
            ->assertJsonPath('0.stops.0.fare', 3.5)
            ->assertJsonPath('0.stops.1.bus_stop_id', $d->id)
            ->assertJsonPath('0.stops.1.fare', 6.5);
    }

    public function test_from_stop_returns_nothing_for_the_final_stop(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $b);

        $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 10],
        ])->assertSessionHasNoErrors();

        $this->getJson('/api/routes/from-stop/'.$b->id)
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_route_stops_endpoint_returns_ordered_stops(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $b);

        $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 10],
        ])->assertSessionHasNoErrors();

        $this->getJson("/api/routes/{$route->id}/stops")
            ->assertOk()
            ->assertJsonCount(2, 'stops')
            ->assertJsonPath('stops.0.bus_stop_id', $a->id)
            ->assertJsonPath('stops.1.stop.name', 'Circle');
    }

    public function test_segment_fare_endpoint_returns_the_difference(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Amasaman');
        $c = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $c);

        $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 3.50],
            ['bus_stop_id' => $c->id, 'fare_from_origin' => 10],
        ])->assertSessionHasNoErrors();

        $this->getJson("/api/routes/{$route->id}/fare?from_stop_id={$b->id}&to_stop_id={$c->id}")
            ->assertOk()
            ->assertJsonPath('fare', 6.5);
    }

    public function test_segment_fare_rejects_reverse_order(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Amasaman');
        $c = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $c);

        $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 3.50],
            ['bus_stop_id' => $c->id, 'fare_from_origin' => 10],
        ])->assertSessionHasNoErrors();

        $this->getJson("/api/routes/{$route->id}/fare?from_stop_id={$c->id}&to_stop_id={$b->id}")
            ->assertStatus(422);
    }

    public function test_ride_uses_the_route_segment_fare(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Amasaman');
        $c = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $c);

        $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 3.50],
            ['bus_stop_id' => $c->id, 'fare_from_origin' => 10],
        ])->assertSessionHasNoErrors();

        Sanctum::actingAs(User::factory()->create(['role' => 'passenger']));

        $response = $this->postJson('/api/rides', [
            'origin_latitude' => 5.6936,
            'origin_longitude' => -0.3485,
            'origin_address' => 'Amasaman',
            'destination_latitude' => 5.5716,
            'destination_longitude' => -0.0254,
            'destination_address' => 'Circle',
            'payment_method' => 'cash',
            'route_id' => $route->id,
            'origin_stop_id' => $b->id,
            'destination_stop_id' => $c->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('fare_amount', 6.5)
            ->assertJsonPath('route_id', $route->id)
            ->assertJsonPath('origin_stop_id', $b->id)
            ->assertJsonPath('destination_stop_id', $c->id);

        $this->assertDatabaseHas('rides', [
            'route_id' => $route->id,
            'origin_stop_id' => $b->id,
            'destination_stop_id' => $c->id,
            'fare_amount' => 6.5,
        ]);
    }

    public function test_ride_request_rejects_stops_out_of_order(): void
    {
        $a = $this->makeStop('Pokuase');
        $b = $this->makeStop('Amasaman');
        $c = $this->makeStop('Circle');
        $route = $this->makeRoute($a, $c);

        $this->saveStops($route, [
            ['bus_stop_id' => $a->id, 'fare_from_origin' => 0],
            ['bus_stop_id' => $b->id, 'fare_from_origin' => 3.50],
            ['bus_stop_id' => $c->id, 'fare_from_origin' => 10],
        ])->assertSessionHasNoErrors();

        Sanctum::actingAs(User::factory()->create(['role' => 'passenger']));

        $this->postJson('/api/rides', [
            'origin_latitude' => 5.5716,
            'origin_longitude' => -0.0254,
            'origin_address' => 'Circle',
            'destination_latitude' => 5.6936,
            'destination_longitude' => -0.3485,
            'destination_address' => 'Pokuase',
            'payment_method' => 'cash',
            'route_id' => $route->id,
            'origin_stop_id' => $c->id,
            'destination_stop_id' => $a->id,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'These stops are not served by this route in that order.');

        $this->assertDatabaseCount('rides', 0);
    }

    public function test_ride_without_stops_still_uses_the_distance_fare(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'passenger']));

        $response = $this->postJson('/api/rides', [
            'origin_latitude' => 5.6037,
            'origin_longitude' => -0.1870,
            'origin_address' => 'Circle, Accra',
            'destination_latitude' => 5.5716,
            'destination_longitude' => -0.0254,
            'destination_address' => 'Makola, Accra',
            'payment_method' => 'cash',
        ]);

        $response->assertCreated()
            ->assertJsonPath('route_id', null)
            ->assertJsonPath('origin_stop_id', null);

        $this->assertNotNull($response->json('fare_amount'));
    }
}
