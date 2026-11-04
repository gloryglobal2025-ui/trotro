<?php

namespace Tests\Feature;

use App\Models\BusStop;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_uploads_a_bus_stop_photo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/bus-stops', [
            'name' => 'Circle Overhead',
            'image' => UploadedFile::fake()->image('circle.jpg', 800, 600),
        ]);

        $response->assertRedirect('/admin/bus-stops');

        $stop = BusStop::where('name', 'Circle Overhead')->firstOrFail();

        $this->assertNotNull($stop->image_path);
        Storage::disk('public')->assertExists($stop->image_path);
        $this->assertStringContainsString($stop->image_path, $stop->image_url);

        $this->getJson('/api/bus-stops')
            ->assertOk()
            ->assertJsonFragment(['image_url' => $stop->image_url]);
    }

    public function test_replacing_a_bus_stop_photo_removes_the_old_file(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $stop = BusStop::create(['name' => 'Kaneshie']);
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('bus-stops', 'public');
        $stop->update(['image_path' => $oldPath]);

        $this->actingAs($admin)->put("/admin/bus-stops/{$stop->id}", [
            'name' => 'Kaneshie',
            'image' => UploadedFile::fake()->image('new.jpg'),
        ])->assertRedirect('/admin/bus-stops');

        $stop->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($stop->image_path);
        $this->assertNotSame($oldPath, $stop->image_path);
    }

    public function test_bus_stop_photo_rejects_non_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/bus-stops', [
            'name' => 'Bad Upload',
            'image' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('bus_stops', ['name' => 'Bad Upload']);
    }

    public function test_passenger_can_upload_a_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'passenger']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/photo', [
            'photo' => UploadedFile::fake()->image('me.jpg', 400, 400),
        ]);

        $response->assertOk()->assertJsonStructure(['profile_image_url']);

        $user->refresh();

        $this->assertNotNull($user->profile_image);
        Storage::disk('public')->assertExists($user->profile_image);
        $this->assertStringContainsString($user->profile_image, $response->json('profile_image_url'));
    }

    public function test_uploaded_photos_are_served_with_cors_headers(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'passenger']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/photo', [
            'photo' => UploadedFile::fake()->image('me.jpg', 200, 200),
        ]);

        $path = parse_url($response->json('profile_image_url'), PHP_URL_PATH);

        $this->assertStringStartsWith('/media/', $path);

        $this->get($path)
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_media_route_rejects_path_traversal(): void
    {
        Storage::fake('public');

        $this->get('/media/../.env')->assertNotFound();
    }

    public function test_profile_photo_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/auth/photo', [
            'photo' => UploadedFile::fake()->image('me.jpg'),
        ])->assertUnauthorized();
    }

    public function test_driver_uploads_a_vehicle_photo_and_others_cannot(): void
    {
        Storage::fake('public');

        $driver = User::factory()->create(['role' => 'driver']);
        $other = User::factory()->create(['role' => 'driver']);

        $vehicle = Vehicle::create([
            'driver_id' => $driver->id,
            'make' => 'Toyota',
            'model' => 'Hiace',
            'year' => 2016,
            'license_plate' => 'GT-1234-16',
            'color' => 'White',
            'capacity' => 14,
            'vehicle_type' => 'bus',
        ]);

        Sanctum::actingAs($other);
        $this->postJson("/api/vehicles/{$vehicle->id}/photo", [
            'photo' => UploadedFile::fake()->image('van.jpg'),
        ])->assertForbidden();

        Sanctum::actingAs($driver);
        $response = $this->postJson("/api/vehicles/{$vehicle->id}/photo", [
            'photo' => UploadedFile::fake()->image('van.jpg', 900, 600),
        ]);

        $response->assertOk()->assertJsonStructure(['vehicle_image_url']);

        $vehicle->refresh();

        $this->assertNotNull($vehicle->vehicle_image);
        Storage::disk('public')->assertExists($vehicle->vehicle_image);
        $this->getJson('/api/vehicles/'.$vehicle->id)->assertOk();
    }
}
