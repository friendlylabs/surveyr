<?php

namespace App\Support;

use Illuminate\Support\Str;
use Laravel\Fortify\Features;

/**
 * Registration
 *
 * The public sign-up rules, from config/auth.php `registration`. Read here rather than straight from config so the
 * routes (AuthController), the auth pages and account creation (CreateNewUser) agree on them.
 */
class Registration
{
    /**
     * The sign-up page exists: Fortify's registration feature on, registration.enabled, and not invitation-only (then
     * accounts only come from an admin's invite: Admin > Users > Add user).
     */
    public static function open(): bool
    {
        return Features::enabled(Features::registration())
            && config('auth.registration.enabled', true)
            && ! config('auth.registration.allow_invitation_only', false);
    }

    /** The role name a self-registered account gets. */
    public static function defaultRole(): string
    {
        return (string) config('auth.registration.default_role', 'member');
    }

    /** Email domains sign-up is limited to, lowercased; empty means any domain. */
    public static function allowedDomains(): array
    {
        return array_values(array_filter(array_map(
            fn ($domain) => Str::lower(ltrim(trim((string) $domain), '@')),
            (array) config('auth.registration.allow_email_domains', [])
        )));
    }

    /**
     * True when the address is on one of the allowed domains (or no limit is set); an exact domain match, so
     * example.com doesn't let in mail.example.com.
     */
    public static function allowsEmail(string $email): bool
    {
        $domains = self::allowedDomains();

        return ! $domains || in_array(Str::lower(Str::afterLast($email, '@')), $domains, true);
    }
}
