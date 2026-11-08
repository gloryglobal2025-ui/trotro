<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Support\Image;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdvertisementController extends Controller
{
    public function index()
    {
        $adverts = Advertisement::latest()->paginate(12);

        return view('admin.adverts.index', compact('adverts'));
    }

    public function create()
    {
        return view('admin.adverts.create', [
            'advert' => new Advertisement(['placement' => 'home', 'is_active' => true]),
            'placements' => Advertisement::PLACEMENTS,
        ]);
    }

    public function store(Request $request)
    {
        $this->normalizeInput($request);

        $validated = $request->validate($this->rules());

        $image = $request->file('image');
        unset($validated['image']);

        $validated['image_path'] = Image::store($image, 'adverts');

        Advertisement::create($validated);

        return redirect()->route('admin.adverts.index')
            ->with('success', 'Advert created. It is live on the selected placement while active.');
    }

    public function edit(Advertisement $advert)
    {
        return view('admin.adverts.edit', [
            'advert' => $advert,
            'placements' => Advertisement::PLACEMENTS,
        ]);
    }

    public function update(Request $request, Advertisement $advert)
    {
        $this->normalizeInput($request);

        $validated = $request->validate($this->rules(false));

        $image = $request->file('image');
        unset($validated['image']);

        if ($image) {
            Image::delete($advert->image_path);
            $validated['image_path'] = Image::store($image, 'adverts');
        }

        $advert->update($validated);

        return redirect()->route('admin.adverts.index')
            ->with('success', 'Advert updated successfully.');
    }

    public function toggle(Advertisement $advert)
    {
        $advert->update(['is_active' => ! $advert->is_active]);

        return redirect()->route('admin.adverts.index')
            ->with('success', $advert->is_active ? 'Advert is now active.' : 'Advert is now paused.');
    }

    public function destroy(Advertisement $advert)
    {
        Image::delete($advert->image_path);
        $advert->delete();

        return redirect()->route('admin.adverts.index')
            ->with('success', 'Advert deleted successfully.');
    }

    private function rules(bool $requiresImage = true): array
    {
        return [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image' => ($requiresImage ? 'required' : 'nullable').'|image|mimes:jpeg,png,jpg,webp|max:4096',
            'link_url' => ['nullable', 'string', 'max:2048', function (string $attribute, $value, \Closure $fail) {
                if (! preg_match('#^(https?://[^\s]+|tel:\+?[0-9()\-]+|mailto:[^\s]+)$#i', $value)) {
                    $fail('Enter a website address or a phone link like tel:+233241234567.');
                }
            }],
            'placement' => ['required', Rule::in(array_keys(Advertisement::PLACEMENTS))],
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ];
    }

    private function normalizeInput(Request $request): void
    {
        $link = preg_replace('/\s+/', '', trim((string) $request->input('link_url', '')));

        if ($link !== '' && ! preg_match('#^[a-z][a-z0-9+.-]*:#i', $link)) {
            $link = preg_match('#^\+?[0-9][0-9()-]{6,}$#', $link)
                ? 'tel:'.$link
                : 'https://'.ltrim($link, '/');
        }

        $request->merge([
            'link_url' => $link === '' ? null : $link,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => max(0, (int) $request->input('sort_order', 0)),
        ]);
    }
}
