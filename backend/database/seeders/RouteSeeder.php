<?php

namespace Database\Seeders;

use App\Models\BusStop;
use App\Models\Route;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $kaneshie = BusStop::where('name', 'Kaneshie')->first();
        $circle = BusStop::where('name', 'Circle')->first();
        $makola = BusStop::where('name', 'Makola')->first();
        $tema = BusStop::where('name', 'Tema Station')->first();
        $madina = BusStop::where('name', 'Madina')->first();
        $achimota = BusStop::where('name', 'Achimota')->first();
        $lapaz = BusStop::where('name', 'Lapaz')->first();
        $kasoa = BusStop::where('name', 'Kasoa')->first();

        $routes = [
            [
                'origin_stop_id' => $kaneshie->id,
                'destination_stop_id' => $circle->id,
                'route_name' => 'Kaneshie to Circle',
                'description' => 'Direct route via Ring Road',
                'estimated_duration' => 15,
            ],
            [
                'origin_stop_id' => $circle->id,
                'destination_stop_id' => $makola->id,
                'route_name' => 'Circle to Makola',
                'description' => 'Route through central Accra',
                'estimated_duration' => 20,
            ],
            [
                'origin_stop_id' => $kaneshie->id,
                'destination_stop_id' => $tema->id,
                'route_name' => 'Kaneshie to Tema',
                'description' => 'Long distance route to Tema',
                'estimated_duration' => 45,
            ],
            [
                'origin_stop_id' => $madina->id,
                'destination_stop_id' => $circle->id,
                'route_name' => 'Madina to Circle',
                'description' => 'Route via N1 highway',
                'estimated_duration' => 25,
            ],
            [
                'origin_stop_id' => $achimota->id,
                'destination_stop_id' => $lapaz->id,
                'route_name' => 'Achimota to Lapaz',
                'description' => 'Short route',
                'estimated_duration' => 10,
            ],
            [
                'origin_stop_id' => $kasoa->id,
                'destination_stop_id' => $kaneshie->id,
                'route_name' => 'Kasoa to Kaneshie',
                'description' => 'Route from Central to Accra',
                'estimated_duration' => 30,
            ],
        ];

        foreach ($routes as $route) {
            Route::create($route);
        }
    }
}
