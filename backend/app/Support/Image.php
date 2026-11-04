<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Image
{
    public const DISK = 'public';

    public static function store(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, self::DISK);
    }

    public static function url(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        // Prefer the host the client used (emulators reach 10.0.2.2, not APP_URL).
        $host = request()->getSchemeAndHttpHost();

        if ($host !== '') {
            return rtrim($host, '/').'/media/'.ltrim($value, '/');
        }

        return Storage::disk(self::DISK)->url($value);
    }

    public static function delete(?string $value): void
    {
        if ($value === null || $value === '' || Str::startsWith($value, ['http://', 'https://'])) {
            return;
        }

        Storage::disk(self::DISK)->delete($value);
    }
}
