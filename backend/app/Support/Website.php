<?php

namespace App\Support;

use App\Helpers\SettingHelper;
use Illuminate\Support\Facades\Cache;

class Website
{
    private const HOMEPAGE_VERSION_KEY = 'website.home.version';

    public const DEFAULTS = [
        'hero_badge' => 'SIMPLE · SAFE · LOCAL',
        'hero_title' => 'Know your route. Know your fare. Go.',
        'hero_subtitle' => 'Find a trotro, see the price before you board, and pay with cash or your Trotro wallet. Built for everyday journeys across Ghana.',
        'hero_cta' => 'Open the app',
        'hero_secondary_cta' => 'Drive with Trotro',
        'about_title' => 'Built for everyday commuters',
        'about_body' => 'Trotro brings route information, clear fares and verified drivers into one simple app. No haggling, no guessing — just the journey you already know, made easier.',
        'contact_phone' => '+233 24 000 0000',
        'contact_email' => 'hello@trotro.digitalsecuritymarket.com',
        'contact_address' => 'Accra, Ghana',
        'footer_text' => 'Trotro — simple, safe journeys across Ghana.',
        'play_store_url' => '',
        'apk_url' => '',
        'meta_title' => '',
        'meta_description' => '',
        'hero_point_1_title' => 'Clear fares',
        'hero_point_1_body' => 'Price before you board',
        'hero_point_2_title' => 'Verified drivers',
        'hero_point_2_body' => 'Checked before approval',
        'hero_point_3_title' => 'Cash or wallet',
        'hero_point_3_body' => 'Pay the way you prefer',
        'about_eyebrow' => 'How it works',
        'step_1_title' => 'Pick where you are',
        'step_1_body' => 'Choose your stop from photos and names, not long lists of text.',
        'step_2_title' => 'Pick where you are going',
        'step_2_body' => 'See the fare for each destination before you leave the house.',
        'step_3_title' => 'Board a verified trotro',
        'step_3_body' => 'Drivers are reviewed before they can accept trips on Trotro.',
        'step_4_title' => 'Pay cash or wallet',
        'step_4_body' => 'Hand over cash or tap your QR pass. The amount is never a surprise.',
        'routes_eyebrow' => 'Popular routes',
        'routes_title' => 'Where Trotro can take you',
        'routes_link_label' => 'See all routes in the app',
        'stops_eyebrow' => 'Stops',
        'stops_title' => 'Know the stop when you see it',
        'stops_body' => 'Photos of real stops help every rider — including people who cannot read the sign — find the right place to board.',
        'drivers_eyebrow' => 'For drivers',
        'drivers_title' => 'Fill your trotro. Skip the shouting.',
        'drivers_body' => 'Riders see your route, your stop and the fare up front. Collect cash as usual, or let riders tap a QR pass — no extra machine needed.',
        'drivers_secondary_cta' => 'Talk to our team',
        'driver_point_1_title' => 'Verified driver badge',
        'driver_point_1_body' => 'Upload your licence and vehicle once, then get approved by our team.',
        'driver_point_2_title' => 'Fares set by admin',
        'driver_point_2_body' => 'No arguing at the roadside — the app shows the agreed price for each leg.',
        'driver_point_3_title' => 'Scan and charge',
        'driver_point_3_body' => 'Tap & Ride passes let repeat riders board in seconds.',
        'contact_eyebrow' => 'Contact',
        'contact_title' => 'Questions, lost items, or a bad trip?',
        'contact_body' => 'Call or message us and a real person from the Trotro team will help. We also want to hear from drivers and transport unions who want to join.',
        'cta_eyebrow' => 'Ready to ride?',
        'cta_title' => 'Open Trotro and see your fare in seconds.',
        'cta_body' => 'Works in your browser today — no download required. Add it to your home screen and it behaves like a normal app.',
    ];

    public const LABELS = [
        'hero_badge' => 'Hero badge',
        'hero_title' => 'Hero headline',
        'hero_subtitle' => 'Hero paragraph',
        'hero_cta' => 'Main button label',
        'hero_secondary_cta' => 'Second button label',
        'about_title' => 'About heading',
        'about_body' => 'About paragraph',
        'contact_phone' => 'Contact phone',
        'contact_email' => 'Contact email',
        'contact_address' => 'Contact address',
        'footer_text' => 'Footer text',
        'play_store_url' => 'Play Store link',
        'apk_url' => 'Android APK link',
        'show_routes' => 'Show routes section',
        'hero_image' => 'Hero image',
        'meta_title' => 'Search title',
        'meta_description' => 'Search description',
        'share_image' => 'Social share image',
        'hero_point_1_title' => 'Hero point 1 title',
        'hero_point_1_body' => 'Hero point 1 text',
        'hero_point_2_title' => 'Hero point 2 title',
        'hero_point_2_body' => 'Hero point 2 text',
        'hero_point_3_title' => 'Hero point 3 title',
        'hero_point_3_body' => 'Hero point 3 text',
        'about_eyebrow' => 'About eyebrow',
        'step_1_title' => 'Step 1 title',
        'step_1_body' => 'Step 1 text',
        'step_2_title' => 'Step 2 title',
        'step_2_body' => 'Step 2 text',
        'step_3_title' => 'Step 3 title',
        'step_3_body' => 'Step 3 text',
        'step_4_title' => 'Step 4 title',
        'step_4_body' => 'Step 4 text',
        'routes_eyebrow' => 'Routes eyebrow',
        'routes_title' => 'Routes heading',
        'routes_link_label' => 'Routes link label',
        'stops_eyebrow' => 'Stops eyebrow',
        'stops_title' => 'Stops heading',
        'stops_body' => 'Stops paragraph',
        'drivers_eyebrow' => 'Drivers eyebrow',
        'drivers_title' => 'Drivers heading',
        'drivers_body' => 'Drivers paragraph',
        'drivers_secondary_cta' => 'Drivers second button',
        'driver_point_1_title' => 'Driver point 1 title',
        'driver_point_1_body' => 'Driver point 1 text',
        'driver_point_2_title' => 'Driver point 2 title',
        'driver_point_2_body' => 'Driver point 2 text',
        'driver_point_3_title' => 'Driver point 3 title',
        'driver_point_3_body' => 'Driver point 3 text',
        'contact_eyebrow' => 'Contact eyebrow',
        'contact_title' => 'Contact heading',
        'contact_body' => 'Contact paragraph',
        'cta_eyebrow' => 'Closing panel eyebrow',
        'cta_title' => 'Closing panel heading',
        'cta_body' => 'Closing panel paragraph',
    ];

    public static function content(): array
    {
        $content = [];

        foreach (self::DEFAULTS as $key => $default) {
            $content[$key] = SettingHelper::get("website_{$key}", $default);
        }

        $content['show_routes'] = (bool) SettingHelper::get('website_show_routes', true);
        $content['hero_image_url'] = Image::url(SettingHelper::get('website_hero_image'));
        $content['share_image_url'] = Image::url(SettingHelper::get('website_share_image'));

        return $content;
    }

    public static function key(string $field): string
    {
        return "website_{$field}";
    }

    public static function homepageCacheKey(): string
    {
        $version = (int) Cache::get(self::HOMEPAGE_VERSION_KEY, 1);

        return 'website.home.v'.$version.'.'.sha1(request()->getHost());
    }

    public static function forgetHomepageCache(): void
    {
        $version = (int) Cache::get(self::HOMEPAGE_VERSION_KEY, 1);

        Cache::forever(self::HOMEPAGE_VERSION_KEY, $version + 1);
    }
}
