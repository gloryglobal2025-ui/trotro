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
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'remove_hero_image' => 'boolean',
        ]);

        $image = $request->file('hero_image');
        $removeImage = $request->boolean('remove_hero_image');
        unset($validated['hero_image'], $validated['remove_hero_image']);

        if ($image) {
            $old = SettingHelper::get('website_hero_image');

            if ($old) {
                Image::delete($old);
            }

            $validated['hero_image'] = Image::store($image, 'website');
        } elseif ($removeImage) {
            Image::delete(SettingHelper::get('website_hero_image'));
            $validated['hero_image'] = '';
        }

        foreach ($validated as $field => $value) {
            $this->put($field, (string) ($value ?? ''));
        }

        return back()->with('success', 'Website content updated.');
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
