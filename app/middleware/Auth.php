<?php

namespace App\Middleware;

use App\Models\User;

class Auth
{
    protected static $uri;
    protected static $user;
    protected static $uriRules;
    
    protected static $home;
    protected static $login;

    protected static $routes;

    public static function load(): bool
    {
        self::initialize();
        self::webRequestAccess(self::getExpressionRules());

        return true;
    }

    protected static function initialize(): void
    {
        self::$user = auth()->user() ?? null;

        self::$home = AuthConfig('GUARD_HOME');
        self::$login = AuthConfig('GUARD_LOGIN');

        self::$routes = [
            '2fa' => AuthConfig('GUARD_2FA'),
            'verify' => AuthConfig('GUARD_VERIFY'),
            'logout' => AuthConfig('GUARD_LOGOUT')
        ];
        
        self::$uri = ltrim($_SERVER['REQUEST_URI'], '/');
        self::$uriRules = require_once RoutesPath('guard.php');
    }

    public static function getExpressionRules(): array
    {
        if (isset(self::$uriRules[self::$uri])) {
            return self::$uriRules[self::$uri];
        }

        foreach (self::$uriRules as $pattern => $rules) {
            $regex = '#^' . strtr(preg_quote($pattern, '#'), [
                '\{int\}' => '(\d+)',           # number based values
                '\{slug\}' => '([a-z0-9-]+)',   # alpha numerical values
                '\{any\}' => '([^/]+?)',        # anything except slashes
                '\{wild\}' => '(.*)'            # wild card
            ]) . '$#i';

            if (preg_match($regex, self::$uri, $matches)) {
                return $rules;
            }
        }

        return ['session' => false, 'access' => 'all'];
    }

    protected static function webRequestAccess($rules): void
    {
        if ($rules['session'] && !self::$user) {
            exit(header("Location: " . self::$login));
        }

        # user logged but not 2fa passed
        if(self::$user && !session()->has('session_id') && !in_array('/'.self::$uri, self::$routes)){
            exit(header("Location: " . self::$routes['2fa']));
        }

        # pages for guest access but user is logged in
        if ($rules['access'] === 'guest' && self::$user) {
            exit(header("Location: " . self::$home));
        }

        # pages for logged in users only
        if (is_array($rules['access'])) {
            if (self::$user && !in_array(self::$user['role'], $rules['access'])) {
                exit(header("Location: " . self::$home));
            }
        } elseif ($rules['access'] !== 'all') {
            if (self::$user && self::$user['role'] !== $rules['access']) {
                exit(header("Location: " . self::$home));
            }
        }
    }
}