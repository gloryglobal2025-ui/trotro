<?php

namespace App\Support;

use App\Helpers\SettingHelper;

class Website
{
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
    ];

    public static function content(): array
    {
        $content = [];

        foreach (self::DEFAULTS as $key => $default) {
            $content[$key] = SettingHelper::get("website_{$key}", $default);
        }

        $content['show_routes'] = (bool) SettingHelper::get('website_show_routes', true);
        $content['hero_image_url'] = Image::url(SettingHelper::get('website_hero_image'));

        return $content;
    }

    public static function key(string $field): string
    {
        return "website_{$field}";
    }
}
