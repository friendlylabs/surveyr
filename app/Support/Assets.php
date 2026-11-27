<?php

namespace App\Support;

/**
 * Assets
 *
 * URLs for the hand-written files in public/assets (the pc-* kit, page scripts) with the file's last-changed time
 * appended, so browsers fetch a new copy as soon as it changes instead of running a cached one. In views: @asset('...').
 */
class Assets
{
    /** Each file's stamp, read once per request. */
    private static array $stamps = [];

    /** The file's URL, ?v= its last-changed time (none when the file is missing). */
    public static function url(string $path): string
    {
        $path = ltrim($path, '/');
        self::$stamps[$path] ??= (string) (@filemtime(public_path($path)) ?: '');

        return asset($path).(self::$stamps[$path] !== '' ? '?v='.self::$stamps[$path] : '');
    }
}
