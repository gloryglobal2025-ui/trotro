<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use Illuminate\Http\Request;

class RideController extends Controller
{
    public function index()
    {
        $rides = Ride::with(['passenger', 'driver', 'vehicle'])
            ->latest()
            ->paginate(20);

        return view('admin.rides.index', compact('rides'));
    }

    public function show(Ride $ride)
    {
        $ride->load(['passenger', 'driver', 'vehicle']);

        return view('admin.rides.show', compact('ride'));
    }

    public function update(Request $request, Ride $ride)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,accepted,driver_arrived,in_progress,completed,cancelled',
        ]);

        $ride->update($validated);

        return redirect()
            ->route('admin.rides.show', $ride)
            ->with('success', 'Ride status updated successfully.');
    }

    public function tracking(Ride $ride)
    {
        $ride->load(['passenger', 'driver', 'vehicle']);

        return view('admin.rides.tracking', compact('ride'));
    }

    public function trackingData(Ride $ride)
    {
        return response()->json([
            'ride' => $ride,
            'driver_location' => [
                'latitude' => $ride->driver_current_latitude,
                'longitude' => $ride->driver_current_longitude,
                'last_update' => $ride->driver_last_location_update,
            ],
        ]);
    }
}
