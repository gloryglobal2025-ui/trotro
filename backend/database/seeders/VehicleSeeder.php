<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $kofi = User::where('email', 'kofi@example.com')->first();
        $ama = User::where('email', 'ama@example.com')->first();

        if ($kofi) {
            Vehicle::create([
                'driver_id' => $kofi->id,
                'make' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2020,
                'license_plate' => 'GW-1234-20',
                'color' => 'White',
                'capacity' => 4,
                'vehicle_type' => 'sedan',
                'is_active' => true,
            ]);
        }

        if ($ama) {
            Vehicle::create([
                'driver_id' => $ama->id,
                'make' => 'Hyundai',
                'model' => 'Accent',
                'year' => 2021,
                'license_plate' => 'GW-5678-21',
                'color' => 'Silver',
                'capacity' => 4,
                'vehicle_type' => 'sedan',
                'is_active' => true,
            ]);
        }
    }
}
