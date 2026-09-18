<?php
declare(strict_types=1);

namespace App;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public static function path(): string
    {
        return '/' . trim(parse_url(self::uri(), PHP_URL_PATH) ?: '/', '/');
    }

    public static function input(string $key, ?string $default = null): ?string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    public static function raw(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function boolean(string $key): bool
    {
        return in_array(self::input($key), ['1', 'true', 'on', 'yes'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::input($key);
        return $value !== null && $value !== '' ? (int) $value : $default;
    }

    public static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xrw = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return str_contains($accept, 'application/json')
            || strtolower($xrw) === 'fetch'
            || strtolower($xrw) === 'xmlhttprequest';
    }

    public static function file(string $key): ?array
    {
        return isset($_FILES[$key]) && is_array($_FILES[$key]) ? $_FILES[$key] : null;
    }
}
