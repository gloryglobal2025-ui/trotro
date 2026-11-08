<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\BusStop;
use App\Models\Route;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_website_renders_with_default_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Know your route. Know your fare. Go.')
            ->assertSee('Open the app');
    }

    public function test_public_website_is_cacheable_and_uses_local_css(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertHeader('Cache-Control', 'max-age=60, public, s-maxage=300, stale-while-revalidate=600')
            ->assertSee('css/website.css', false)
            ->assertDontSee('cdn.tailwindcss.com')
            ->assertSee('application/ld+json', false)
            ->assertSee('MobileApplication', false)
            ->assertSee('<link rel="canonical"', false);
    }

    public function test_sitemap_lists_the_homepage(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset', false)
            ->assertSee(url('/'), false);
    }

    public function test_admin_update_changes_the_public_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/website', [
            'hero_badge' => 'NEW BADGE',
            'hero_title' => 'Ride across Accra for less',
            'hero_subtitle' => 'Clear fares on every route.',
            'hero_cta' => 'Start riding',
            'hero_secondary_cta' => 'Join as a driver',
            'about_title' => 'About the service',
            'about_body' => 'We make everyday journeys simple.',
            'contact_phone' => '+233 20 111 2222',
            'contact_email' => 'team@trotro.test',
            'contact_address' => 'Kumasi, Ghana',
            'footer_text' => 'Trotro, made in Ghana.',
            'show_routes' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'key' => 'website_hero_title',
            'value' => 'Ride across Accra for less',
            'group' => 'website',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ride across Accra for less')
            ->assertSee('Start riding')
            ->assertSee('team@trotro.test')
            ->assertSee('Trotro, made in Ghana.');
    }

    public function test_admin_can_edit_seo_and_section_copy(): void
    {
        $origin = BusStop::create(['name' => 'Circle', 'area' => 'Accra']);
        $destination = BusStop::create(['name' => 'Kejetia', 'area' => 'Kumasi']);
        Route::create([
            'origin_stop_id' => $origin->id,
            'destination_stop_id' => $destination->id,
            'route_name' => 'Accra to Kumasi',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/website', $this->form([
            'meta_title' => 'Trotro fares in Accra',
            'meta_description' => 'See trotro fares before you board.',
            'about_eyebrow' => 'Start here',
            'step_1_title' => 'Choose your stop',
            'routes_title' => 'Routes we cover',
            'drivers_title' => 'Drive with us',
            'contact_title' => 'Need help?',
            'cta_title' => 'Ride today',
        ]))->assertRedirect();

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Trotro fares in Accra</title>', false)
            ->assertSee('<meta name="description" content="See trotro fares before you board.">', false)
            ->assertSee('Start here')
            ->assertSee('Choose your stop')
            ->assertSee('Routes we cover')
            ->assertSee('Drive with us')
            ->assertSee('Need help?')
            ->assertSee('Ride today');
    }

    public function test_admin_can_upload_and_remove_the_share_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/website', $this->form([
            'share_image' => UploadedFile::fake()->image('share.jpg', 1200, 630),
        ]))->assertRedirect();

        $path = Setting::where('key', 'website_share_image')->value('value');
        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($admin)->put('/admin/website', $this->form([
            'remove_share_image' => '1',
        ]))->assertRedirect();

        $this->assertSame('', Setting::where('key', 'website_share_image')->value('value'));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_upload_and_remove_the_hero_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/website', $this->form([
            'hero_image' => UploadedFile::fake()->image('hero.jpg', 1600, 1200),
        ]))->assertRedirect();

        $path = Setting::where('key', 'website_hero_image')->value('value');
        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($admin)->put('/admin/website', $this->form([
            'remove_hero_image' => '1',
        ]))->assertRedirect();

        $this->assertSame('', Setting::where('key', 'website_hero_image')->value('value'));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_website_shows_only_website_adverts(): void
    {
        Advertisement::create([
            'title' => 'Website sponsor',
            'image_path' => 'adverts/site.jpg',
            'placement' => 'website',
            'is_active' => true,
        ]);
        Advertisement::create([
            'title' => 'App only sponsor',
            'image_path' => 'adverts/app.jpg',
            'placement' => 'home',
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Website sponsor')
            ->assertDontSee('App only sponsor');
    }

    public function test_routes_section_can_be_hidden(): void
    {
        $origin = BusStop::create(['name' => 'Circle', 'area' => 'Accra']);
        $destination = BusStop::create(['name' => 'Kejetia', 'area' => 'Kumasi']);
        Route::create([
            'origin_stop_id' => $origin->id,
            'destination_stop_id' => $destination->id,
            'route_name' => 'Accra to Kumasi',
        ]);

        $this->get('/')->assertOk()->assertSee('Popular routes');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/website', $this->form([
            'show_routes' => '0',
        ]))->assertRedirect();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Popular routes');
    }

    public function test_admin_can_view_the_website_editor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/website')
            ->assertOk()
            ->assertSee('Website content')
            ->assertSee('Know your route. Know your fare. Go.')
            ->assertSee('Show the popular routes section')
            ->assertSee('Search and sharing')
            ->assertSee('Four steps')
            ->assertSee('Drivers section');
    }

    public function test_guests_and_non_admins_cannot_edit_the_website(): void
    {
        $this->get('/admin/website')->assertRedirect(route('admin.login'));

        $passenger = User::factory()->create(['role' => 'passenger']);

        $this->actingAs($passenger)->get('/admin/website')->assertForbidden();
        $this->actingAs($passenger)
            ->put('/admin/website', $this->form())
            ->assertForbidden();
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'hero_badge' => 'SIMPLE',
            'hero_title' => 'Know your route. Know your fare. Go.',
            'hero_subtitle' => 'Clear fares on every journey.',
            'hero_cta' => 'Open the app',
            'hero_secondary_cta' => 'Drive with Trotro',
            'about_title' => 'Built for everyday commuters',
            'about_body' => 'Route information and clear fares in one simple app.',
            'contact_phone' => '+233 24 000 0000',
            'contact_email' => 'hello@trotro.test',
            'contact_address' => 'Accra, Ghana',
            'footer_text' => 'Simple, safe journeys.',
            'show_routes' => '1',
        ], $overrides);
    }
}
