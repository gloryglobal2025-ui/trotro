<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusStop;
use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function index()
    {
        $routes = Route::with(['origin', 'destination'])->latest()->paginate(10);

        return view('admin.routes.index', compact('routes'));
    }

    public function create()
    {
        $busStops = BusStop::all();

        return view('admin.routes.create', compact('busStops'));
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

        Route::create($validated);

        return redirect()->route('admin.routes.index')
            ->with('success', 'Route created successfully.');
    }

    public function show(Route $route)
    {
        $route->load(['origin', 'destination', 'fares', 'stops.stop']);

        return view('admin.routes.show', compact('route'));
    }

    public function stops(Route $route)
    {
        $route->load(['origin', 'destination', 'stops.stop']);
        $busStops = BusStop::orderBy('name')->get();

        return view('admin.routes.stops', compact('route', 'busStops'));
    }

    public function updateStops(Request $request, Route $route)
    {
        $validated = $request->validate([
            'stops' => ['required', 'array', 'min:2'],
            'stops.*.bus_stop_id' => ['required', 'integer', 'exists:bus_stops,id', 'distinct'],
            'stops.*.fare_from_origin' => ['required', 'numeric', 'min:0'],
        ]);

        $stops = collect($validated['stops'])->values();

        if ((int) $stops->first()['bus_stop_id'] !== $route->origin_stop_id) {
            return back()->withInput()->withErrors([
                'stops' => 'The first stop must be the route origin: '.$route->origin->name.'.',
            ]);
        }

        if ((int) $stops->last()['bus_stop_id'] !== $route->destination_stop_id) {
            return back()->withInput()->withErrors([
                'stops' => 'The last stop must be the route destination: '.$route->destination->name.'.',
            ]);
        }

        $previousFare = 0.0;
        $stopNames = BusStop::whereIn('id', $stops->pluck('bus_stop_id'))->pluck('name', 'id');
        $rows = [];

        foreach ($stops as $index => $stop) {
            $fare = (float) $stop['fare_from_origin'];
            $name = $stopNames[$stop['bus_stop_id']] ?? 'Stop #'.$stop['bus_stop_id'];

            if ($index === 0) {
                $fare = 0.0;
            } elseif ($fare < $previousFare) {
                return back()->withInput()->withErrors([
                    'stops' => "The fare to {$name} (GHS ".number_format($fare, 2).') cannot be less than the fare to the stop before it (GHS '.number_format($previousFare, 2).').',
                ]);
            }

            if ($index === count($stops) - 1 && $fare <= 0) {
                return back()->withInput()->withErrors([
                    'stops' => 'The last stop must have a fare greater than zero.',
                ]);
            }

            $rows[] = [
                'bus_stop_id' => (int) $stop['bus_stop_id'],
                'sequence' => $index + 1,
                'fare_from_origin' => round($fare, 2),
            ];

            $previousFare = $fare;
        }

        DB::transaction(function () use ($route, $rows) {
            $route->stops()->delete();

            foreach ($rows as $row) {
                RouteStop::create($row + ['route_id' => $route->id]);
            }
        });

        return redirect()->route('admin.routes.stops', $route)
            ->with('success', 'Stops and fares updated.');
    }

    public function edit(Route $route)
    {
        $busStops = BusStop::all();

        return view('admin.routes.edit', compact('route', 'busStops'));
    }

    public function update(Request $request, Route $route)
    {
        $validated = $request->validate([
            'origin_stop_id' => 'sometimes|required|exists:bus_stops,id',
            'destination_stop_id' => 'sometimes|required|exists:bus_stops,id|different:origin_stop_id',
            'route_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'estimated_duration' => 'nullable|integer|min:1',
        ]);

        $route->update($validated);

        return redirect()->route('admin.routes.index')
            ->with('success', 'Route updated successfully.');
    }

    public function destroy(Route $route)
    {
        $route->delete();

        return redirect()->route('admin.routes.index')
            ->with('success', 'Route deleted successfully.');
    }
}
