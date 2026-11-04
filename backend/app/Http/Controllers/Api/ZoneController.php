<?php

namespace App\Http\Controllers\Api;

use App\Helpers\SettingHelper;
use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Models\ZoneFare;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index()
    {
        $zones = Zone::where('is_active', true)->get();

        return response()->json($zones);
    }

    public function show(Zone $zone)
    {
        $zone->load('busStops');

        return response()->json($zone);
    }

    public function getZoneForLocation(Request $request)
    {
        if (! SettingHelper::isZoneEnabled()) {
            return response()->json(['message' => 'Zone-based fares are disabled'], 400);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $zones = Zone::where('is_active', true)->get();

        foreach ($zones as $zone) {
            if ($zone->contains($validated['latitude'], $validated['longitude'])) {
                return response()->json($zone);
            }
        }

        return response()->json(['message' => 'No zone found for this location'], 404);
    }

    public function calculateFare(Request $request)
    {
        $validated = $request->validate([
            'from_latitude' => 'required|numeric',
            'from_longitude' => 'required|numeric',
            'to_latitude' => 'required|numeric',
            'to_longitude' => 'required|numeric',
        ]);

        // Find zones for origin and destination
        $fromZone = $this->findZoneForLocation($validated['from_latitude'], $validated['from_longitude']);
        $toZone = $this->findZoneForLocation($validated['to_latitude'], $validated['to_longitude']);

        if (! $fromZone || ! $toZone) {
            return response()->json(['message' => 'Could not determine zones for locations'], 400);
        }

        // Find zone fare
        $zoneFare = ZoneFare::where('from_zone_id', $fromZone->id)
            ->where('to_zone_id', $toZone->id)
            ->where('is_active', true)
            ->first();

        if (! $zoneFare) {
            return response()->json(['message' => 'No fare configuration found for this route'], 404);
        }

        // Calculate distance
        $distanceKm = $this->calculateDistance(
            $validated['from_latitude'],
            $validated['from_longitude'],
            $validated['to_latitude'],
            $validated['to_longitude']
        );

        // Calculate fare
        $fare = $zoneFare->calculateFare($distanceKm);

        return response()->json([
            'from_zone' => $fromZone,
            'to_zone' => $toZone,
            'distance_km' => round($distanceKm, 2),
            'fare' => round($fare, 2),
            'base_fare' => $zoneFare->base_fare,
            'rate_per_km' => $zoneFare->rate_per_km,
        ]);
    }

    private function findZoneForLocation($latitude, $longitude)
    {
        $zones = Zone::where('is_active', true)->get();

        foreach ($zones as $zone) {
            if ($zone->contains($latitude, $longitude)) {
                return $zone;
            }
        }

        return null;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
              cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
              sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
