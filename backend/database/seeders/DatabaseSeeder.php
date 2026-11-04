<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ZoneSeeder::class,
            BusStopSeeder::class,
            RouteSeeder::class,
            RouteStopSeeder::class,
            FareSeeder::class,
            UserSeeder::class,
            VehicleSeeder::class,
            UserLevelSeeder::class,
            WalletSeeder::class,
            WalletBonusSeeder::class,
        ]);
    }
}
