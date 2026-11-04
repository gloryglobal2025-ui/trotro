<?php

namespace Database\Seeders;

use App\Models\BusStop;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class RouteStopSeeder extends Seeder
{
    public function run(): void
    {
        $northZone = Zone::where('code', 'ACC-NORTH')->first();

        $pokuase = BusStop::firstOrCreate(
            ['name' => 'Pokuase'],
            [
                'location' => 'Pokuase Junction',
                'latitude' => 5.6936,
                'longitude' => -0.3485,
                'area' => 'Accra',
                'zone_id' => $northZone?->id,
            ]
        );

        $amasaman = BusStop::firstOrCreate(
            ['name' => 'Amasaman'],
            [
                'location' => 'Amasaman Station',
                'latitude' => 5.7047,
                'longitude' => -0.3017,
                'area' => 'Accra',
                'zone_id' => $northZone?->id,
            ]
        );

        Route::firstOrCreate(
            ['route_name' => 'Pokuase to Circle'],
            [
                'origin_stop_id' => $pokuase->id,
                'destination_stop_id' => BusStop::where('name', 'Circle')->value('id'),
                'description' => 'Route via Amasaman and Achimota',
                'estimated_duration' => 40,
            ]
        );

        $chains = [
            'Pokuase to Circle' => [['Pokuase', 0], ['Amasaman', 3.50], ['Achimota', 7.00], ['Circle', 10.00]],
            'Kaneshie to Circle' => [['Kaneshie', 0], ['Circle', 4.50]],
            'Circle to Makola' => [['Circle', 0], ['Makola', 3.00]],
            'Kaneshie to Tema' => [['Kaneshie', 0], ['Circle', 4.50], ['Tema Station', 12.00]],
            'Madina to Circle' => [['Madina', 0], ['Circle', 7.00]],
            'Achimota to Lapaz' => [['Achimota', 0], ['Lapaz', 3.00]],
            'Kasoa to Kaneshie' => [['Kasoa', 0], ['Kaneshie', 9.00]],
        ];

        foreach ($chains as $routeName => $stops) {
            $route = Route::where('route_name', $routeName)->first();

            if (! $route) {
                continue;
            }

            $route->stops()->delete();

            foreach ($stops as $index => [$stopName, $fare]) {
                $stop = BusStop::where('name', $stopName)->first();

                if (! $stop) {
                    continue;
                }

                RouteStop::create([
                    'route_id' => $route->id,
                    'bus_stop_id' => $stop->id,
                    'sequence' => $index + 1,
                    'fare_from_origin' => $fare,
                ]);
            }
        }
    }
}
