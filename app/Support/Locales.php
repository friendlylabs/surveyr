<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/**
 * Locales
 *
 * The interface languages, from config/surveyr.php `locales`, and how the request's one is chosen: the user's own
 * choice, then their workspace's default, then the browser's language, then app.locale.
 */
class Locales
{
    /**
     * Supported locales, keyed by code.
     *
     * @return array<string, array{name: string, native: string, dir: string}>
     */
    public static function supported(): array
    {
        return (array) config('surveyr.locales', []);
    }

    /** True when the code is one users can pick. */
    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::supported());
    }

    /** The app's default locale: app.locale when supported, else the first configured. */
    public static function default(): string
    {
        $locale = (string) config('app.locale', 'en');

        return self::isSupported($locale) ? $locale : (string) array_key_first(self::supported());
    }

    /** `rtl` or `ltr` for the locale (the current one by default). */
    public static function direction(?string $locale = null): string
    {
        return self::supported()[$locale ?? app()->getLocale()]['dir'] ?? 'ltr';
    }

    /** Picks the locale for the request. */
    public static function resolve(Request $request): string
    {
        $user = $request->user();

        if ($user instanceof User) {
            foreach ([$user->locale, $user->currentWorkspace?->locale] as $locale) {
                if (self::isSupported($locale)) {
                    return $locale;
                }
            }
        }

        $browser = $request->getPreferredLanguage(array_keys(self::supported()));

        return self::isSupported($browser) ? $browser : self::default();
    }

    /**
     * The current locale's JSON translations, handed to the browser for pc.t() (public/assets/js/pc-core.js).
     *
     * @return array<string, string>
     */
    public static function strings(?string $locale = null): array
    {
        $path = lang_path(($locale ?? app()->getLocale()).'.json');

        return File::exists($path) ? (array) json_decode(File::get($path), true) : [];
    }
}
