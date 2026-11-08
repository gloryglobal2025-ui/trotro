<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvertisementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoint_returns_only_live_adverts_for_the_placement(): void
    {
        Advertisement::create([
            'title' => 'Live home banner',
            'image_path' => 'adverts/live.jpg',
            'placement' => 'home',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        Advertisement::create([
            'title' => 'First home banner',
            'image_path' => 'adverts/first.jpg',
            'placement' => 'home',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Advertisement::create([
            'title' => 'Paused banner',
            'image_path' => 'adverts/paused.jpg',
            'placement' => 'home',
            'is_active' => false,
        ]);
        Advertisement::create([
            'title' => 'Expired banner',
            'image_path' => 'adverts/expired.jpg',
            'placement' => 'home',
            'is_active' => true,
            'ends_at' => now()->subDay(),
        ]);
        Advertisement::create([
            'title' => 'Future banner',
            'image_path' => 'adverts/future.jpg',
            'placement' => 'home',
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);
        Advertisement::create([
            'title' => 'Driver banner',
            'image_path' => 'adverts/driver.jpg',
            'placement' => 'driver',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/advertisements?placement=home');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.title', 'First home banner')
            ->assertJsonPath('1.title', 'Live home banner');
    }

    public function test_unknown_placement_falls_back_to_home(): void
    {
        Advertisement::create([
            'title' => 'Home banner',
            'image_path' => 'adverts/home.jpg',
            'placement' => 'home',
            'is_active' => true,
        ]);

        $this->getJson('/api/advertisements?placement=sidebar')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Home banner');
    }

    public function test_admin_can_create_an_advert(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/adverts', [
            'title' => 'Kejetia market offer',
            'subtitle' => 'This weekend only',
            'image' => UploadedFile::fake()->image('banner.jpg', 1200, 500),
            'link_url' => 'kejetia.example.com',
            'placement' => 'home',
            'is_active' => '1',
            'sort_order' => 3,
        ]);

        $response->assertRedirect(route('admin.adverts.index'));

        $advert = Advertisement::firstOrFail();
        $this->assertSame('Kejetia market offer', $advert->title);
        $this->assertSame('https://kejetia.example.com', $advert->link_url);
        $this->assertTrue($advert->is_active);
        $this->assertSame(3, $advert->sort_order);
        Storage::disk('public')->assertExists($advert->image_path);
    }

    public function test_phone_number_link_becomes_a_call_link(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/adverts', [
            'title' => 'Call the shop',
            'image' => UploadedFile::fake()->image('banner.jpg'),
            'link_url' => '024 123 4567',
            'placement' => 'home',
            'is_active' => '1',
        ])->assertRedirect(route('admin.adverts.index'));

        $this->assertSame('tel:0241234567', Advertisement::firstOrFail()->link_url);
    }

    public function test_advert_links_must_be_safe(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/adverts', [
            'title' => 'Bad link',
            'image' => UploadedFile::fake()->image('banner.jpg'),
            'link_url' => 'javascript:alert(1)',
            'placement' => 'home',
            'is_active' => '1',
        ])->assertSessionHasErrors('link_url');

        $this->assertDatabaseCount('advertisements', 0);
    }

    public function test_admin_can_pause_and_resume_an_advert(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $advert = Advertisement::create([
            'title' => 'Paused soon',
            'image_path' => 'adverts/pause.jpg',
            'placement' => 'home',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.adverts.toggle', $advert))
            ->assertRedirect(route('admin.adverts.index'));

        $this->assertFalse($advert->fresh()->is_active);

        $this->actingAs($admin)->post(route('admin.adverts.toggle', $advert));

        $this->assertTrue($advert->fresh()->is_active);
    }

    public function test_admin_can_delete_an_advert_and_its_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('adverts/delete-me.jpg', 'image');

        $admin = User::factory()->create(['role' => 'admin']);
        $advert = Advertisement::create([
            'title' => 'Delete me',
            'image_path' => 'adverts/delete-me.jpg',
            'placement' => 'home',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.adverts.destroy', $advert))
            ->assertRedirect(route('admin.adverts.index'));

        $this->assertDatabaseMissing('advertisements', ['id' => $advert->id]);
        Storage::disk('public')->assertMissing('adverts/delete-me.jpg');
    }

    public function test_admin_can_view_advert_screens(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $advert = Advertisement::create([
            'title' => 'Screen check',
            'image_path' => 'adverts/screen.jpg',
            'placement' => 'home',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get('/admin/adverts')->assertOk()->assertSee('Adverts');
        $this->actingAs($admin)->get('/admin/adverts/create')->assertOk()->assertSee('New advert');
        $this->actingAs($admin)->get(route('admin.adverts.edit', $advert))->assertOk()->assertSee('Screen check');
    }

    public function test_non_admin_cannot_manage_adverts(): void
    {
        $passenger = User::factory()->create(['role' => 'passenger']);

        $this->actingAs($passenger)->get('/admin/adverts')->assertForbidden();
    }
}
