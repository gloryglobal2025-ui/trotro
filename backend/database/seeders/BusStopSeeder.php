<?php

namespace Database\Seeders;

use App\Models\BusStop;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class BusStopSeeder extends Seeder
{
    public function run(): void
    {
        $zones = Zone::all()->keyBy('code');

        $busStops = [
            ['name' => 'Kaneshie', 'location' => 'Kaneshie Main Market', 'latitude' => 5.5600, 'longitude' => -0.2400, 'area' => 'Accra', 'zone_code' => 'ACC-WEST'],
            ['name' => 'Circle', 'location' => 'Kwame Nkrumah Circle', 'latitude' => 5.5700, 'longitude' => -0.2100, 'area' => 'Accra', 'zone_code' => 'ACC-WEST'],
            ['name' => 'Makola', 'location' => 'Makola Market', 'latitude' => 5.5500, 'longitude' => -0.2200, 'area' => 'Accra', 'zone_code' => 'ACC-CENTRAL'],
            ['name' => 'Tema Station', 'location' => 'Tema Station', 'latitude' => 5.6700, 'longitude' => -0.0100, 'area' => 'Tema', 'zone_code' => 'ACC-SOUTH'],
            ['name' => 'Madina', 'location' => 'Madina Market', 'latitude' => 5.6800, 'longitude' => -0.1700, 'area' => 'Accra', 'zone_code' => 'ACC-EAST'],
            ['name' => 'Achimota', 'location' => 'Achimota Forest', 'latitude' => 5.6100, 'longitude' => -0.2300, 'area' => 'Accra', 'zone_code' => 'ACC-NORTH'],
            ['name' => 'Lapaz', 'location' => 'Lapaz Market', 'latitude' => 5.5900, 'longitude' => -0.2500, 'area' => 'Accra', 'zone_code' => 'ACC-WEST'],
            ['name' => 'Kasoa', 'location' => 'Kasoa Central', 'latitude' => 5.5200, 'longitude' => -0.4400, 'area' => 'Central', 'zone_code' => 'ACC-WEST'],
            ['name' => 'Spintex', 'location' => 'Spintex Road', 'latitude' => 5.6300, 'longitude' => -0.1400, 'area' => 'Accra', 'zone_code' => 'ACC-EAST'],
            ['name' => 'East Legon', 'location' => 'East Legon', 'latitude' => 5.6500, 'longitude' => -0.1500, 'area' => 'Accra', 'zone_code' => 'ACC-EAST'],
        ];

        foreach ($busStops as $busStop) {
            $zoneId = $zones[$busStop['zone_code']]?->id;
            BusStop::create([
                'name' => $busStop['name'],
                'location' => $busStop['location'],
                'latitude' => $busStop['latitude'],
                'longitude' => $busStop['longitude'],
                'area' => $busStop['area'],
                'zone_id' => $zoneId,
            ]);
        }
    }
}
