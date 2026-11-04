<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusStop;
use App\Support\Image;
use Illuminate\Http\Request;

class BusStopController extends Controller
{
    public function index()
    {
        $busStops = BusStop::latest()->paginate(10);

        return view('admin.bus-stops.index', compact('busStops'));
    }

    public function create()
    {
        return view('admin.bus-stops.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'area' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $image = $request->file('image');
        unset($validated['image']);

        if ($image) {
            $validated['image_path'] = Image::store($image, 'bus-stops');
        }

        BusStop::create($validated);

        return redirect()->route('admin.bus-stops.index')
            ->with('success', 'Bus stop created successfully.');
    }

    public function show(BusStop $busStop)
    {
        return view('admin.bus-stops.show', compact('busStop'));
    }

    public function edit(BusStop $busStop)
    {
        return view('admin.bus-stops.edit', compact('busStop'));
    }

    public function update(Request $request, BusStop $busStop)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'area' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $image = $request->file('image');
        unset($validated['image']);

        if ($image) {
            Image::delete($busStop->image_path);
            $validated['image_path'] = Image::store($image, 'bus-stops');
        }

        $busStop->update($validated);

        return redirect()->route('admin.bus-stops.index')
            ->with('success', 'Bus stop updated successfully.');
    }

    public function destroy(BusStop $busStop)
    {
        Image::delete($busStop->image_path);
        $busStop->delete();

        return redirect()->route('admin.bus-stops.index')
            ->with('success', 'Bus stop deleted successfully.');
    }
}
