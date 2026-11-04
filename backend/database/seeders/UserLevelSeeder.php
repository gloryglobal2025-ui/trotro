<?php

namespace Database\Seeders;

use App\Models\UserLevel;
use Illuminate\Database\Seeder;

class UserLevelSeeder extends Seeder
{
    public function run(): void
    {
        // Driver Levels
        UserLevel::create([
            'name' => 'Bronze Driver',
            'slug' => 'bronze-driver',
            'user_type' => 'driver',
            'commission_rate' => 15.00,
            'fare_discount' => 0.00,
            'daily_rides_limit' => 20,
            'min_rating' => 4.0,
            'is_active' => true,
            'order' => 1,
        ]);

        UserLevel::create([
            'name' => 'Silver Driver',
            'slug' => 'silver-driver',
            'user_type' => 'driver',
            'commission_rate' => 12.00,
            'fare_discount' => 2.00,
            'daily_rides_limit' => 30,
            'min_rating' => 4.3,
            'is_active' => true,
            'order' => 2,
        ]);

        UserLevel::create([
            'name' => 'Gold Driver',
            'slug' => 'gold-driver',
            'user_type' => 'driver',
            'commission_rate' => 10.00,
            'fare_discount' => 5.00,
            'daily_rides_limit' => 50,
            'min_rating' => 4.5,
            'is_active' => true,
            'order' => 3,
        ]);

        UserLevel::create([
            'name' => 'Platinum Driver',
            'slug' => 'platinum-driver',
            'user_type' => 'driver',
            'commission_rate' => 8.00,
            'fare_discount' => 8.00,
            'daily_rides_limit' => null,
            'min_rating' => 4.7,
            'is_active' => true,
            'order' => 4,
        ]);

        // Customer Levels
        UserLevel::create([
            'name' => 'New Customer',
            'slug' => 'new-customer',
            'user_type' => 'customer',
            'commission_rate' => 0.00,
            'fare_discount' => 0.00,
            'daily_rides_limit' => null,
            'min_rating' => 4.0,
            'is_active' => true,
            'order' => 1,
        ]);

        UserLevel::create([
            'name' => 'Regular Customer',
            'slug' => 'regular-customer',
            'user_type' => 'customer',
            'commission_rate' => 0.00,
            'fare_discount' => 5.00,
            'daily_rides_limit' => null,
            'min_rating' => 4.0,
            'is_active' => true,
            'order' => 2,
        ]);

        UserLevel::create([
            'name' => 'VIP Customer',
            'slug' => 'vip-customer',
            'user_type' => 'customer',
            'commission_rate' => 0.00,
            'fare_discount' => 10.00,
            'daily_rides_limit' => null,
            'min_rating' => 4.0,
            'is_active' => true,
            'order' => 3,
        ]);

        UserLevel::create([
            'name' => 'Premium Customer',
            'slug' => 'premium-customer',
            'user_type' => 'customer',
            'commission_rate' => 0.00,
            'fare_discount' => 15.00,
            'daily_rides_limit' => null,
            'min_rating' => 4.0,
            'is_active' => true,
            'order' => 4,
        ]);
    }
}
