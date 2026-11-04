<?php

namespace App\Http\Controllers\Api;

use App\Helpers\SettingHelper;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::public()->get()->pluck('typed_value', 'key');

        // Always include app name even if not marked as public
        $settings['app_name'] = SettingHelper::getAppName();
        $settings['app_version'] = SettingHelper::getAppVersion();

        return response()->json($settings);
    }

    public function show($key)
    {
        $setting = Setting::where('key', $key)->public()->first();

        if (! $setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        return response()->json([
            'key' => $setting->key,
            'value' => $setting->typed_value,
            'type' => $setting->type,
        ]);
    }

    public function fareCalculation(Request $request)
    {
        $distance = $request->input('distance', 0);
        $duration = $request->input('duration', 0);

        $baseFare = SettingHelper::getBaseFare();
        $farePerKm = SettingHelper::getFarePerKm();
        $farePerMinute = SettingHelper::getFarePerMinute();
        $minFare = SettingHelper::getMinFare();
        $multiplier = SettingHelper::isPeakHour() ? SettingHelper::getPeakHourMultiplier() : 1.0;

        $fare = ($baseFare + ($distance * $farePerKm) + ($duration * $farePerMinute)) * $multiplier;
        $fare = max($fare, $minFare);

        return response()->json([
            'fare' => round($fare, 2),
            'breakdown' => [
                'base_fare' => $baseFare,
                'distance_charge' => round($distance * $farePerKm, 2),
                'time_charge' => round($duration * $farePerMinute, 2),
                'peak_hour_multiplier' => $multiplier,
                'is_peak_hour' => SettingHelper::isPeakHour(),
                'minimum_fare' => $minFare,
            ],
        ]);
    }
}
