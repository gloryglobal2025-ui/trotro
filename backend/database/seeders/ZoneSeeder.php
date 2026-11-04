<?php

namespace Database\Seeders;

use App\Models\Zone;
use App\Models\ZoneFare;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        // Create Accra zones
        $zones = [
            [
                'name' => 'Central Accra',
                'code' => 'ACC-CENTRAL',
                'description' => 'Central business district and surrounding areas',
                'center_latitude' => 5.5716,
                'center_longitude' => -0.0254,
                'radius_km' => 5.0,
            ],
            [
                'name' => 'North Accra',
                'code' => 'ACC-NORTH',
                'description' => 'Achimota, Lapaz, and northern suburbs',
                'center_latitude' => 5.6438,
                'center_longitude' => -0.2334,
                'radius_km' => 8.0,
            ],
            [
                'name' => 'East Accra',
                'code' => 'ACC-EAST',
                'description' => 'East Legon, Spintex, and eastern areas',
                'center_latitude' => 5.6388,
                'center_longitude' => -0.1109,
                'radius_km' => 7.0,
            ],
            [
                'name' => 'West Accra',
                'code' => 'ACC-WEST',
                'description' => 'Kaneshie, Circle, and western areas',
                'center_latitude' => 5.5704,
                'center_longitude' => -0.2468,
                'radius_km' => 6.0,
            ],
            [
                'name' => 'South Accra',
                'code' => 'ACC-SOUTH',
                'description' => 'Makola, Tema, and southern areas',
                'center_latitude' => 5.6706,
                'center_longitude' => -0.0126,
                'radius_km' => 10.0,
            ],
        ];

        $createdZones = [];
        foreach ($zones as $zone) {
            $createdZones[$zone['code']] = Zone::create($zone);
        }

        // Create zone fares
        $zoneFares = [
            // Within same zone
            ['from' => 'ACC-CENTRAL', 'to' => 'ACC-CENTRAL', 'base' => 5.00, 'rate' => 1.50],
            ['from' => 'ACC-NORTH', 'to' => 'ACC-NORTH', 'base' => 5.00, 'rate' => 1.50],
            ['from' => 'ACC-EAST', 'to' => 'ACC-EAST', 'base' => 5.00, 'rate' => 1.50],
            ['from' => 'ACC-WEST', 'to' => 'ACC-WEST', 'base' => 5.00, 'rate' => 1.50],
            ['from' => 'ACC-SOUTH', 'to' => 'ACC-SOUTH', 'base' => 5.00, 'rate' => 1.50],

            // Central to other zones
            ['from' => 'ACC-CENTRAL', 'to' => 'ACC-NORTH', 'base' => 8.00, 'rate' => 2.00],
            ['from' => 'ACC-CENTRAL', 'to' => 'ACC-EAST', 'base' => 8.00, 'rate' => 2.00],
            ['from' => 'ACC-CENTRAL', 'to' => 'ACC-WEST', 'base' => 6.00, 'rate' => 1.80],
            ['from' => 'ACC-CENTRAL', 'to' => 'ACC-SOUTH', 'base' => 10.00, 'rate' => 2.20],

            // North to other zones
            ['from' => 'ACC-NORTH', 'to' => 'ACC-CENTRAL', 'base' => 8.00, 'rate' => 2.00],
            ['from' => 'ACC-NORTH', 'to' => 'ACC-EAST', 'base' => 12.00, 'rate' => 2.50],
            ['from' => 'ACC-NORTH', 'to' => 'ACC-WEST', 'base' => 10.00, 'rate' => 2.30],
            ['from' => 'ACC-NORTH', 'to' => 'ACC-SOUTH', 'base' => 15.00, 'rate' => 2.80],

            // East to other zones
            ['from' => 'ACC-EAST', 'to' => 'ACC-CENTRAL', 'base' => 8.00, 'rate' => 2.00],
            ['from' => 'ACC-EAST', 'to' => 'ACC-NORTH', 'base' => 12.00, 'rate' => 2.50],
            ['from' => 'ACC-EAST', 'to' => 'ACC-WEST', 'base' => 14.00, 'rate' => 2.60],
            ['from' => 'ACC-EAST', 'to' => 'ACC-SOUTH', 'base' => 12.00, 'rate' => 2.40],

            // West to other zones
            ['from' => 'ACC-WEST', 'to' => 'ACC-CENTRAL', 'base' => 6.00, 'rate' => 1.80],
            ['from' => 'ACC-WEST', 'to' => 'ACC-NORTH', 'base' => 10.00, 'rate' => 2.30],
            ['from' => 'ACC-WEST', 'to' => 'ACC-EAST', 'base' => 14.00, 'rate' => 2.60],
            ['from' => 'ACC-WEST', 'to' => 'ACC-SOUTH', 'base' => 12.00, 'rate' => 2.40],

            // South to other zones
            ['from' => 'ACC-SOUTH', 'to' => 'ACC-CENTRAL', 'base' => 10.00, 'rate' => 2.20],
            ['from' => 'ACC-SOUTH', 'to' => 'ACC-NORTH', 'base' => 15.00, 'rate' => 2.80],
            ['from' => 'ACC-SOUTH', 'to' => 'ACC-EAST', 'base' => 12.00, 'rate' => 2.40],
            ['from' => 'ACC-SOUTH', 'to' => 'ACC-WEST', 'base' => 12.00, 'rate' => 2.40],
        ];

        foreach ($zoneFares as $fare) {
            ZoneFare::create([
                'from_zone_id' => $createdZones[$fare['from']]->id,
                'to_zone_id' => $createdZones[$fare['to']]->id,
                'base_fare' => $fare['base'],
                'rate_per_km' => $fare['rate'],
                'min_fare' => 5.00,
            ]);
        }
    }
}
