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

    /**
     * Cloudflare's published proxy ranges (rarely change; see
     * https://www.cloudflare.com/ips/). When the connection actually comes
     * from one of these, the real visitor address is in CF-Connecting-IP —
     * REMOTE_ADDR would otherwise just be Cloudflare's edge, which would
     * make login rate limiting treat every visitor as the same handful of IPs.
     */
    private const CLOUDFLARE_CIDRS = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    private static function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $bits = (int) $bits;
        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;
        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }
        if ($remainder === 0) {
            return true;
        }
        $mask = ~(0xFF >> $remainder) & 0xFF;
        return (ord($ipBin[$bytes]) & $mask) === (ord($subnetBin[$bytes]) & $mask);
    }

    private static function trustedProxyConnection(): bool
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($remote === '') {
            return false;
        }
        foreach (self::CLOUDFLARE_CIDRS as $cidr) {
            if (self::ipInCidr($remote, $cidr)) {
                return true;
            }
        }
        return false;
    }

    public static function ip(): string
    {
        $header = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
        if ($header !== '' && self::trustedProxyConnection() && filter_var($header, FILTER_VALIDATE_IP)) {
            return $header;
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function ipBinary(): string
    {
        $packed = @inet_pton(self::ip());
        return $packed !== false ? $packed : '';
    }

    public static function file(string $key): ?array
    {
        return isset($_FILES[$key]) && is_array($_FILES[$key]) ? $_FILES[$key] : null;
    }
}
