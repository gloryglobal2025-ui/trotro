<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index()
    {
        $routes = Route::with(['origin', 'destination', 'fares'])->get();

        return response()->json($routes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'origin_stop_id' => 'required|exists:bus_stops,id',
            'destination_stop_id' => 'required|exists:bus_stops,id|different:origin_stop_id',
            'route_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'estimated_duration' => 'nullable|integer|min:1',
        ]);

        $route = Route::create($validated);

        return response()->json($route->load(['origin', 'destination']), 201);
    }

    public function show(string $id)
    {
        $route = Route::with(['origin', 'destination', 'fares'])->findOrFail($id);

        return response()->json($route);
    }

    public function update(Request $request, string $id)
    {
        $route = Route::findOrFail($id);
        $validated = $request->validate([
            'origin_stop_id' => 'sometimes|required|exists:bus_stops,id',
            'destination_stop_id' => 'sometimes|required|exists:bus_stops,id|different:origin_stop_id',
            'route_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'estimated_duration' => 'nullable|integer|min:1',
        ]);

        $route->update($validated);

        return response()->json($route->load(['origin', 'destination']));
    }

    public function destroy(string $id)
    {
        $route = Route::findOrFail($id);
        $route->delete();

        return response()->json(null, 204);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'origin_id' => 'required|exists:bus_stops,id',
            'destination_id' => 'required|exists:bus_stops,id',
        ]);

        $routes = Route::with(['origin', 'destination', 'fares'])
            ->where('origin_stop_id', $validated['origin_id'])
            ->where('destination_stop_id', $validated['destination_id'])
            ->get();

        return response()->json($routes);
    }

    public function stops(string $id)
    {
        $route = Route::with(['origin', 'destination', 'stops.stop'])->findOrFail($id);

        return response()->json($route);
    }

    public function fromStop(string $busStopId)
    {
        $entries = RouteStop::where('bus_stop_id', $busStopId)
            ->with(['route.origin', 'route.destination', 'route.stops.stop'])
            ->get();

        $options = $entries->map(function (RouteStop $entry) {
            $downstream = $entry->route->stops
                ->where('sequence', '>', $entry->sequence)
                ->map(fn (RouteStop $stop) => [
                    'bus_stop_id' => $stop->bus_stop_id,
                    'name' => $stop->stop?->name,
                    'sequence' => $stop->sequence,
                    'fare_from_origin' => (float) $stop->fare_from_origin,
                    'fare' => round((float) $stop->fare_from_origin - (float) $entry->fare_from_origin, 2),
                ])
                ->values();

            if ($downstream->isEmpty()) {
                return null;
            }

            return [
                'route_id' => $entry->route->id,
                'route_name' => $entry->route->route_name,
                'origin_name' => $entry->route->origin?->name,
                'destination_stop_id' => $entry->route->destination_stop_id,
                'destination_name' => $entry->route->destination?->name,
                'boarding_stop_id' => $entry->bus_stop_id,
                'boarding_sequence' => $entry->sequence,
                'stops' => $downstream,
            ];
        })->filter()->values();

        return response()->json($options);
    }

    public function segmentFare(Request $request, string $id)
    {
        $validated = $request->validate([
            'from_stop_id' => 'required|integer|exists:bus_stops,id',
            'to_stop_id' => 'required|integer|exists:bus_stops,id|different:from_stop_id',
        ]);

        $route = Route::with('stops')->findOrFail($id);
        $fare = $route->fareBetween($validated['from_stop_id'], $validated['to_stop_id']);

        if ($fare === null) {
            return response()->json([
                'message' => 'Those stops are not on this route in that order.',
            ], 422);
        }

        return response()->json([
            'route_id' => $route->id,
            'from_stop_id' => (int) $validated['from_stop_id'],
            'to_stop_id' => (int) $validated['to_stop_id'],
            'fare' => $fare,
        ]);
    }
}
