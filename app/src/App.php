<?php
declare(strict_types=1);

namespace App;

/**
 * Tiny service locator. Holds config + lazily-built shared services.
 */
final class App
{
    private static array $config = [];
    private static array $services = [];

    public static function boot(array $config): void
    {
        self::$config = $config;
    }

    /** @return mixed */
    public static function config(string $key, $default = null)
    {
        $segments = explode('.', $key);
        $value = self::$config;
        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        return $value;
    }

    public static function allConfig(): array
    {
        return self::$config;
    }

    public static function db(): Database
    {
        return self::$services['db'] ??= new Database(self::$config['db']);
    }

    public static function auth(): Auth
    {
        return self::$services['auth'] ??= new Auth(self::db());
    }

    public static function mailer(): Mailer
    {
        return self::$services['mailer'] ??= new Mailer(self::$config['mail'], self::$config['storage_path']);
    }

    public static function limiter(): RateLimiter
    {
        return self::$services['limiter'] ??= new RateLimiter(self::db());
    }
}
