<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SettingHelper;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Image;
use App\Support\Website;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    public function edit()
    {
        $content = Website::content();

        return view('admin.website.edit', compact('content'));
    }

    public function update(Request $request)
    {
        $request->merge([
            'show_routes' => $request->boolean('show_routes') ? '1' : '0',
        ]);

        $validated = $request->validate([
            'hero_badge' => 'nullable|string|max:100',
            'hero_title' => 'required|string|max:120',
            'hero_subtitle' => 'required|string|max:300',
            'hero_cta' => 'required|string|max:40',
            'hero_secondary_cta' => 'required|string|max:40',
            'about_title' => 'required|string|max:120',
            'about_body' => 'required|string|max:2000',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'contact_address' => 'nullable|string|max:255',
            'footer_text' => 'nullable|string|max:255',
            'play_store_url' => 'nullable|url:http,https|max:2048',
            'apk_url' => 'nullable|url:http,https|max:2048',
            'show_routes' => 'required|boolean',
            'meta_title' => 'nullable|string|max:120',
            'meta_description' => 'nullable|string|max:320',
            'hero_point_1_title' => 'nullable|string|max:60',
            'hero_point_1_body' => 'nullable|string|max:120',
            'hero_point_2_title' => 'nullable|string|max:60',
            'hero_point_2_body' => 'nullable|string|max:120',
            'hero_point_3_title' => 'nullable|string|max:60',
            'hero_point_3_body' => 'nullable|string|max:120',
            'about_eyebrow' => 'nullable|string|max:60',
            'step_1_title' => 'nullable|string|max:80',
            'step_1_body' => 'nullable|string|max:300',
            'step_2_title' => 'nullable|string|max:80',
            'step_2_body' => 'nullable|string|max:300',
            'step_3_title' => 'nullable|string|max:80',
            'step_3_body' => 'nullable|string|max:300',
            'step_4_title' => 'nullable|string|max:80',
            'step_4_body' => 'nullable|string|max:300',
            'routes_eyebrow' => 'nullable|string|max:60',
            'routes_title' => 'nullable|string|max:120',
            'routes_link_label' => 'nullable|string|max:60',
            'stops_eyebrow' => 'nullable|string|max:60',
            'stops_title' => 'nullable|string|max:120',
            'stops_body' => 'nullable|string|max:400',
            'drivers_eyebrow' => 'nullable|string|max:60',
            'drivers_title' => 'nullable|string|max:120',
            'drivers_body' => 'nullable|string|max:400',
            'drivers_secondary_cta' => 'nullable|string|max:40',
            'driver_point_1_title' => 'nullable|string|max:80',
            'driver_point_1_body' => 'nullable|string|max:300',
            'driver_point_2_title' => 'nullable|string|max:80',
            'driver_point_2_body' => 'nullable|string|max:300',
            'driver_point_3_title' => 'nullable|string|max:80',
            'driver_point_3_body' => 'nullable|string|max:300',
            'contact_eyebrow' => 'nullable|string|max:60',
            'contact_title' => 'nullable|string|max:120',
            'contact_body' => 'nullable|string|max:400',
            'cta_eyebrow' => 'nullable|string|max:60',
            'cta_title' => 'nullable|string|max:120',
            'cta_body' => 'nullable|string|max:400',
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'remove_hero_image' => 'boolean',
            'share_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'remove_share_image' => 'boolean',
        ]);

        $this->handleImage($request, $validated, 'hero_image', 'remove_hero_image');
        $this->handleImage($request, $validated, 'share_image', 'remove_share_image');

        foreach ($validated as $field => $value) {
            $this->put($field, (string) ($value ?? ''));
        }

        Website::forgetHomepageCache();

        return back()->with('success', 'Website content updated.');
    }

    private function handleImage(Request $request, array &$validated, string $field, string $removeField): void
    {
        $file = $request->file($field);
        $remove = $request->boolean($removeField);

        unset($validated[$field], $validated[$removeField]);

        if ($file) {
            $old = SettingHelper::get(Website::key($field));

            if ($old) {
                Image::delete($old);
            }

            $validated[$field] = Image::store($file, 'website');
        } elseif ($remove) {
            Image::delete(SettingHelper::get(Website::key($field)));
            $validated[$field] = '';
        }
    }

    private function put(string $field, string $value): void
    {
        $key = Website::key($field);

        Setting::updateOrCreate(['key' => $key], [
            'value' => $value,
            'type' => $field === 'show_routes' ? 'boolean' : 'string',
            'group' => 'website',
            'display_name' => Website::LABELS[$field] ?? ucfirst(str_replace('_', ' ', $field)),
            'description' => 'Editable from the Website page.',
            'is_public' => true,
        ]);

        SettingHelper::clearCache($key);
    }
}
