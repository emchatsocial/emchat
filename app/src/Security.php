<?php
declare(strict_types=1);

namespace App;

/**
 * Abuse protection for forms that send email (sign-in, contact):
 * the visitor's real IP behind Cloudflare, and Cloudflare Turnstile
 * (a privacy-friendly captcha). Turnstile stays off until both keys are set
 * in config 'turnstile' => ['site_key' => ..., 'secret_key' => ...].
 */
final class Security
{
    /** Visitor IP. Behind Cloudflare the real one is in CF-Connecting-IP. */
    public static function clientIp(): string
    {
        $cf = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '');
        if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
            return $cf;
        }
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function turnstileEnabled(): bool
    {
        $t = (array) App::config('turnstile', []);
        return ($t['site_key'] ?? '') !== '' && ($t['secret_key'] ?? '') !== '';
    }

    /** The widget + its script, for inside a form. Empty when Turnstile is off. */
    public static function turnstileWidget(): string
    {
        if (!self::turnstileEnabled()) {
            return '';
        }
        $key = (string) App::config('turnstile')['site_key'];
        return '<div class="cf-turnstile" data-sitekey="' . e($key) . '" data-theme="auto" data-size="flexible" data-appearance="always"></div>'
            . '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer nonce="' . e(csp_nonce()) . '"></script>';
    }

    /** True when Turnstile is off, or Cloudflare confirms this request's token. */
    public static function turnstileOk(): bool
    {
        if (!self::turnstileEnabled()) {
            return true;
        }
        $token = (string) ($_POST['cf-turnstile-response'] ?? '');
        if ($token === '' || strlen($token) > 2048 || !function_exists('curl_init')) {
            return false;
        }
        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'secret'   => (string) App::config('turnstile')['secret_key'],
                'response' => $token,
                'remoteip' => self::clientIp(),
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $data = is_string($res) ? json_decode($res, true) : null;
        return is_array($data) && ($data['success'] ?? false) === true;
    }
}
