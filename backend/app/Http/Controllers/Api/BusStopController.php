<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusStop;
use Illuminate\Http\Request;

class BusStopController extends Controller
{
    public function index()
    {
        $busStops = BusStop::all();

        return response()->json($busStops);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'area' => 'nullable|string|max:255',
        ]);

        $busStop = BusStop::create($validated);

        return response()->json($busStop, 201);
    }

    public function show(string $id)
    {
        $busStop = BusStop::with(['routesAsOrigin', 'routesAsDestination'])->findOrFail($id);

        return response()->json($busStop);
    }

    public function update(Request $request, string $id)
    {
        $busStop = BusStop::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'area' => 'nullable|string|max:255',
        ]);

        $busStop->update($validated);

        return response()->json($busStop);
    }

    public function destroy(string $id)
    {
        $busStop = BusStop::findOrFail($id);
        $busStop->delete();

        return response()->json(null, 204);
    }
}
