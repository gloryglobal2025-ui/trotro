<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment(['local', 'testing'])) {
            $this->seedDemoUsers();
        }

        $this->seedAdmin();
    }

    private function seedDemoUsers(): void
    {
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password'),
                'phone' => '+233201234567',
                'role' => 'passenger',
                'rating' => 4.5,
                'total_rides' => 15,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => Hash::make('password'),
                'phone' => '+233202345678',
                'role' => 'passenger',
                'rating' => 5.0,
                'total_rides' => 23,
            ],
            [
                'name' => 'Kofi Mensah',
                'email' => 'kofi@example.com',
                'password' => Hash::make('password'),
                'phone' => '+233203456789',
                'role' => 'driver',
                'rating' => 4.8,
                'total_rides' => 150,
            ],
            [
                'name' => 'Ama Boateng',
                'email' => 'ama@example.com',
                'password' => Hash::make('password'),
                'phone' => '+233204567890',
                'role' => 'driver',
                'rating' => 4.6,
                'total_rides' => 200,
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(['email' => $user['email']], $user);
        }
    }

    private function seedAdmin(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set; skipping admin creation.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make($password),
                'phone' => env('ADMIN_PHONE', ''),
                'role' => 'admin',
                'rating' => 5.0,
                'total_rides' => 0,
            ]
        );
    }
}
