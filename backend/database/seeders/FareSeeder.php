<?php

namespace Database\Seeders;

use App\Models\Fare;
use App\Models\Route;
use Illuminate\Database\Seeder;

class FareSeeder extends Seeder
{
    public function run(): void
    {
        $routes = Route::all();

        foreach ($routes as $route) {
            Fare::create([
                'route_id' => $route->id,
                'amount' => $this->calculateFare($route->estimated_duration),
                'fare_type' => 'regular',
                'description' => 'Standard fare for '.$route->route_name,
                'is_active' => true,
                'effective_date' => now(),
            ]);

            Fare::create([
                'route_id' => $route->id,
                'amount' => $this->calculateFare($route->estimated_duration) * 0.5,
                'fare_type' => 'student',
                'description' => 'Student fare for '.$route->route_name,
                'is_active' => true,
                'effective_date' => now(),
            ]);
        }
    }

    private function calculateFare($duration)
    {
        return round(2.00 + ($duration * 0.10), 2);
    }
}
