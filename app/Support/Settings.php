<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Settings
 *
 * The install's settings an admin changes in the app (the settings table, values encrypted), read over their .env
 * defaults: Settings::get('storage.driver', config('surveyr.uploads.disk')). Read once per request (and cached for a
 * minute); writing one forgets the cache.
 */
class Settings
{
    /** The cache key holding every setting. */
    const CACHE = 'surveyr.settings';

    /** @var array<string, mixed>|null */
    private static ?array $loaded = null;

    /** A setting's value, or the default when it isn't set. */
    public static function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, self::all()) ? self::all()[$key] : $default;
    }

    /** Sets a setting (null removes it). */
    public static function put(string $key, mixed $value): void
    {
        if ($value === null) {
            Setting::where('key', $key)->delete();
        } else {
            Setting::updateOrCreate(['key' => $key], ['value' => ['value' => $value]]);
        }
        self::forget();
    }

    /** Drops what's been read, so the next read goes to the database. */
    public static function forget(): void
    {
        self::$loaded = null;
        Cache::forget(self::CACHE);
    }

    /** @return array<string, mixed> */
    private static function all(): array
    {
        if (self::$loaded !== null) {
            return self::$loaded;
        }

        try {
            // encrypted at rest; the cache holds them as stored (the app key decrypts them)
            $rows = Cache::remember(self::CACHE, 60, fn () => Schema::hasTable('settings') ? Setting::all()->map->getAttributes()->all() : []);
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['key']] = (new Setting)->setRawAttributes($row)->value['value'] ?? null;
            }
        } catch (Throwable) {
            // no database yet (installing, some console commands): the defaults stand
            $settings = [];
        }

        return self::$loaded = $settings;
    }
}
