<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index()
    {
        $zones = Zone::with('busStops')->latest()->get();

        return view('admin.zones.index', compact('zones'));
    }

    public function create()
    {
        return view('admin.zones.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:zones',
            'description' => 'nullable|string',
            'center_latitude' => 'required|numeric',
            'center_longitude' => 'required|numeric',
            'radius_km' => 'required|numeric|min:0.1',
            'is_active' => 'boolean',
        ]);

        Zone::create($validated);

        return redirect()
            ->route('admin.zones.index')
            ->with('success', 'Zone created successfully.');
    }

    public function show(Zone $zone)
    {
        $zone->load(['busStops', 'fromZoneFares.toZone', 'toZoneFares.fromZone']);

        return view('admin.zones.show', compact('zone'));
    }

    public function edit(Zone $zone)
    {
        return view('admin.zones.edit', compact('zone'));
    }

    public function update(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:zones,code,'.$zone->id,
            'description' => 'nullable|string',
            'center_latitude' => 'required|numeric',
            'center_longitude' => 'required|numeric',
            'radius_km' => 'required|numeric|min:0.1',
            'is_active' => 'boolean',
        ]);

        $zone->update($validated);

        return redirect()
            ->route('admin.zones.index')
            ->with('success', 'Zone updated successfully.');
    }

    public function destroy(Zone $zone)
    {
        $zone->delete();

        return redirect()
            ->route('admin.zones.index')
            ->with('success', 'Zone deleted successfully.');
    }
}
