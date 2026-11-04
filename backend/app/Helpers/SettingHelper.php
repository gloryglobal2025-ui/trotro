<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingHelper
{
    public static function get(string $key, $default = null)
    {
        $setting = Cache::remember("setting_{$key}", 3600, function () use ($key) {
            $setting = Setting::where('key', $key)->first();
            if (! $setting) {
                return null;
            }

            return [
                'value' => $setting->value,
                'type' => $setting->type,
            ];
        });

        if (! $setting) {
            return $default;
        }

        return match ($setting['type']) {
            'boolean' => filter_var($setting['value'], FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting['value'],
            'float' => (float) $setting['value'],
            'json' => json_decode($setting['value'], true),
            default => $setting['value'],
        };
    }

    public static function set(string $key, $value): void
    {
        $setting = Setting::where('key', $key)->first();
        if ($setting) {
            $setting->update(['value' => $value]);
            Cache::forget("setting_{$key}");
        }
    }

    public static function group(string $group): array
    {
        return Cache::remember("settings_group_{$group}", 3600, function () use ($group) {
            $settings = Setting::byGroup($group)->get();
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = match ($setting->type) {
                    'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                    'integer' => (int) $setting->value,
                    'float' => (float) $setting->value,
                    'json' => json_decode($setting->value, true),
                    default => $setting->value,
                };
            }

            return $result;
        });
    }

    public static function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget("setting_{$key}");
        } else {
            Cache::forget('settings_all');
        }
    }

    public static function refreshCache(): void
    {
        Cache::flush();
    }

    // Fare Settings
    public static function getBaseFare(): float
    {
        return (float) self::get('base_fare', 5.00);
    }

    public static function getFarePerKm(): float
    {
        return (float) self::get('fare_per_km', 2.00);
    }

    public static function getFarePerMinute(): float
    {
        return (float) self::get('fare_per_minute', 0.50);
    }

    public static function getMinFare(): float
    {
        return (float) self::get('min_fare', 5.00);
    }

    public static function getCancellationFee(): float
    {
        return (float) self::get('cancellation_fee', 2.00);
    }

    public static function getStudentDiscount(): int
    {
        return (int) self::get('student_discount_percentage', 20);
    }

    public static function getSeniorDiscount(): int
    {
        return (int) self::get('senior_discount_percentage', 15);
    }

    public static function getPeakHourMultiplier(): float
    {
        return (float) self::get('peak_hour_multiplier', 1.5);
    }

    public static function isPeakHour(): bool
    {
        $now = now();
        $start = self::get('peak_hour_start', '07:00');
        $end = self::get('peak_hour_end', '09:00');

        return $now->format('H:i') >= $start && $now->format('H:i') <= $end;
    }

    // Ride Settings
    public static function getRideTimeout(): int
    {
        return (int) self::get('ride_timeout', 15);
    }

    public static function getWaitingTimeLimit(): int
    {
        return (int) self::get('waiting_time_limit', 5);
    }

    // Payment Settings
    public static function getPaymentMethods(): array
    {
        $methods = (array) self::get('payment_methods', ['cash', 'wallet']);

        return array_values(array_unique(array_merge($methods, ['wallet'])));
    }

    public static function isPaymentMethodEnabled(string $method): bool
    {
        return in_array($method, self::getPaymentMethods());
    }

    // Map Settings
    public static function getMapProvider(): string
    {
        return self::get('map_provider', 'openstreetmap');
    }

    public static function getGoogleMapsApiKey(): ?string
    {
        return self::get('google_maps_api_key');
    }

    public static function getDefaultMapCenter(): array
    {
        return [
            'lat' => (float) self::get('default_map_center_lat', 5.6037),
            'lng' => (float) self::get('default_map_center_lng', -0.1870),
        ];
    }

    public static function getDefaultMapZoom(): int
    {
        return (int) self::get('default_map_zoom', 12);
    }

    // Zone Settings
    public static function isZoneEnabled(): bool
    {
        return (bool) self::get('zone_enabled', true);
    }

    public static function getDefaultZoneRadius(): float
    {
        return (float) self::get('default_zone_radius', 5.0);
    }

    public static function getInterZoneBaseFare(): float
    {
        return (float) self::get('inter_zone_base_fare', 8.00);
    }

    // General Settings
    public static function getAppName(): string
    {
        return self::get('app_name', 'Trotro');
    }

    public static function getAppVersion(): string
    {
        return self::get('app_version', '1.0.0');
    }

    public static function isMaintenanceMode(): bool
    {
        return (bool) self::get('maintenance_mode', false);
    }

    // Notification Settings
    public static function isSmsEnabled(): bool
    {
        return (bool) self::get('sms_enabled', false);
    }

    public static function isEmailEnabled(): bool
    {
        return (bool) self::get('email_enabled', true);
    }

    public static function isPushEnabled(): bool
    {
        return (bool) self::get('push_enabled', false);
    }
}
