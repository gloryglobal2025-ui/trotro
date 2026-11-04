<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            ['key' => 'app_name', 'value' => 'Trotro', 'type' => 'string', 'group' => 'general', 'display_name' => 'Application Name', 'description' => 'The name of the application', 'is_public' => true],
            ['key' => 'app_version', 'value' => '1.0.0', 'type' => 'string', 'group' => 'general', 'display_name' => 'Application Version', 'description' => 'Current version of the application', 'is_public' => true],
            ['key' => 'support_email', 'value' => 'support@trotro.com', 'type' => 'string', 'group' => 'general', 'display_name' => 'Support Email', 'description' => 'Email address for customer support', 'is_public' => true],
            ['key' => 'support_phone', 'value' => '+233 XX XXX XXXX', 'type' => 'string', 'group' => 'general', 'display_name' => 'Support Phone', 'description' => 'Phone number for customer support', 'is_public' => true],
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'general', 'display_name' => 'Maintenance Mode', 'description' => 'Enable maintenance mode to disable the app', 'is_public' => false],

            // Payment Settings
            ['key' => 'payment_methods', 'value' => '["cash","wallet"]', 'type' => 'json', 'group' => 'payment', 'display_name' => 'Available Payment Methods', 'description' => 'List of available payment methods', 'is_public' => true],
            ['key' => 'momo_provider', 'value' => 'mtn', 'type' => 'string', 'group' => 'payment', 'display_name' => 'Mobile Money Provider', 'description' => 'Mobile money provider (mtn, vodafone, airteltigo)', 'is_public' => false],
            ['key' => 'momo_api_key', 'value' => '', 'type' => 'string', 'group' => 'payment', 'display_name' => 'Mobile Money API Key', 'description' => 'API key for mobile money integration', 'is_public' => false],
            ['key' => 'card_processor', 'value' => 'paystack', 'type' => 'string', 'group' => 'payment', 'display_name' => 'Card Payment Processor', 'description' => 'Card payment processor (paystack, flutterwave)', 'is_public' => false],
            ['key' => 'card_public_key', 'value' => '', 'type' => 'string', 'group' => 'payment', 'display_name' => 'Card Public Key', 'description' => 'Public key for card payments', 'is_public' => false],
            ['key' => 'card_secret_key', 'value' => '', 'type' => 'string', 'group' => 'payment', 'display_name' => 'Card Secret Key', 'description' => 'Secret key for card payments', 'is_public' => false],

            // Ride Settings
            ['key' => 'min_fare', 'value' => '5.00', 'type' => 'float', 'group' => 'ride', 'display_name' => 'Minimum Fare', 'description' => 'Minimum fare for any ride', 'is_public' => true],
            ['key' => 'base_fare', 'value' => '5.00', 'type' => 'float', 'group' => 'ride', 'display_name' => 'Base Fare', 'description' => 'Base fare for rides', 'is_public' => true],
            ['key' => 'fare_per_km', 'value' => '2.00', 'type' => 'float', 'group' => 'ride', 'display_name' => 'Fare Per Kilometer', 'description' => 'Rate charged per kilometer', 'is_public' => true],
            ['key' => 'fare_per_minute', 'value' => '0.50', 'type' => 'float', 'group' => 'ride', 'display_name' => 'Fare Per Minute', 'description' => 'Rate charged per minute of ride time', 'is_public' => true],
            ['key' => 'cancellation_fee', 'value' => '2.00', 'type' => 'float', 'group' => 'ride', 'display_name' => 'Cancellation Fee', 'description' => 'Fee charged for ride cancellation', 'is_public' => true],
            ['key' => 'waiting_time_limit', 'value' => '5', 'type' => 'integer', 'group' => 'ride', 'display_name' => 'Waiting Time Limit', 'description' => 'Maximum waiting time in minutes before cancellation', 'is_public' => true],
            ['key' => 'ride_timeout', 'value' => '15', 'type' => 'integer', 'group' => 'ride', 'display_name' => 'Ride Request Timeout', 'description' => 'Time in minutes before ride request expires', 'is_public' => true],

            // Map Settings
            ['key' => 'map_provider', 'value' => 'openstreetmap', 'type' => 'string', 'group' => 'map', 'display_name' => 'Map Provider', 'description' => 'Map provider (openstreetmap, googlemaps)', 'is_public' => true],
            ['key' => 'google_maps_api_key', 'value' => '', 'type' => 'string', 'group' => 'map', 'display_name' => 'Google Maps API Key', 'description' => 'API key for Google Maps', 'is_public' => false],
            ['key' => 'default_map_center_lat', 'value' => '5.6037', 'type' => 'float', 'group' => 'map', 'display_name' => 'Default Map Center Latitude', 'description' => 'Default latitude for map center', 'is_public' => true],
            ['key' => 'default_map_center_lng', 'value' => '-0.1870', 'type' => 'float', 'group' => 'map', 'display_name' => 'Default Map Center Longitude', 'description' => 'Default longitude for map center', 'is_public' => true],
            ['key' => 'default_map_zoom', 'value' => '12', 'type' => 'integer', 'group' => 'map', 'display_name' => 'Default Map Zoom Level', 'description' => 'Default zoom level for maps', 'is_public' => true],

            // Notification Settings
            ['key' => 'sms_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'notification', 'display_name' => 'SMS Notifications', 'description' => 'Enable SMS notifications', 'is_public' => false],
            ['key' => 'sms_provider', 'value' => 'twilio', 'type' => 'string', 'group' => 'notification', 'display_name' => 'SMS Provider', 'description' => 'SMS provider (twilio, africastalking)', 'is_public' => false],
            ['key' => 'sms_api_key', 'value' => '', 'type' => 'string', 'group' => 'notification', 'display_name' => 'SMS API Key', 'description' => 'API key for SMS provider', 'is_public' => false],
            ['key' => 'email_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'notification', 'display_name' => 'Email Notifications', 'description' => 'Enable email notifications', 'is_public' => false],
            ['key' => 'push_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'notification', 'display_name' => 'Push Notifications', 'description' => 'Enable push notifications', 'is_public' => false],
            ['key' => 'push_server_key', 'value' => '', 'type' => 'string', 'group' => 'notification', 'display_name' => 'Push Server Key', 'description' => 'Server key for push notifications', 'is_public' => false],

            // Fare Settings
            ['key' => 'student_discount_percentage', 'value' => '20', 'type' => 'integer', 'group' => 'fare', 'display_name' => 'Student Discount Percentage', 'description' => 'Discount percentage for students', 'is_public' => true],
            ['key' => 'senior_discount_percentage', 'value' => '15', 'type' => 'integer', 'group' => 'fare', 'display_name' => 'Senior Discount Percentage', 'description' => 'Discount percentage for senior citizens', 'is_public' => true],
            ['key' => 'peak_hour_multiplier', 'value' => '1.5', 'type' => 'float', 'group' => 'fare', 'display_name' => 'Peak Hour Multiplier', 'description' => 'Fare multiplier during peak hours', 'is_public' => true],
            ['key' => 'peak_hour_start', 'value' => '07:00', 'type' => 'string', 'group' => 'fare', 'display_name' => 'Peak Hour Start', 'description' => 'Start time for peak hours', 'is_public' => true],
            ['key' => 'peak_hour_end', 'value' => '09:00', 'type' => 'string', 'group' => 'fare', 'display_name' => 'Peak Hour End', 'description' => 'End time for peak hours', 'is_public' => true],

            // Zone Settings
            ['key' => 'zone_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'zone', 'display_name' => 'Zone-Based Fares', 'description' => 'Enable zone-based fare calculation', 'is_public' => true],
            ['key' => 'default_zone_radius', 'value' => '5', 'type' => 'float', 'group' => 'zone', 'display_name' => 'Default Zone Radius', 'description' => 'Default radius for zones in kilometers', 'is_public' => true],
            ['key' => 'inter_zone_base_fare', 'value' => '8.00', 'type' => 'float', 'group' => 'zone', 'display_name' => 'Inter-Zone Base Fare', 'description' => 'Base fare for rides between different zones', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
