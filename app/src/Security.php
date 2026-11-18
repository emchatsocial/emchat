<?php
declare(strict_types=1);

namespace App;

/**
 * Abuse protection for forms that send email (sign-in, contact):
 * the visitor's real IP behind Cloudflare, a small always-visible picture
 * check ("tap the cat"), and Cloudflare Turnstile (a privacy-friendly captcha
 * that is always shown as a box). Turnstile stays off until
 * both keys are set in config 'turnstile' => ['site_key' => ..., 'secret_key' => ...].
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

    /** Simple drawings for the picture check (48x48). Names are shown in the question only. */
    private const PICTURES = [
        'cat'      => '<path d="M10 20 12 4l11 9zM38 20 36 4l-11 9z" fill="#f4a340"/><circle cx="24" cy="27" r="15" fill="#f4a340"/><circle cx="18" cy="25" r="2.2" fill="#222"/><circle cx="30" cy="25" r="2.2" fill="#222"/><path d="M21.5 30.5h5L24 33z" fill="#d9534f"/><path d="M5 30h9M6 34l8-2M43 30h-9M42 34l-8-2" stroke="#7a4a12" stroke-width="1.4" stroke-linecap="round"/>',
        'fish'     => '<path d="M32 24 45 14v20z" fill="#2f7fe0"/><ellipse cx="21" cy="24" rx="15" ry="10" fill="#3b8ff5"/><circle cx="13" cy="22" r="2.6" fill="#fff"/><circle cx="12.6" cy="22" r="1.3" fill="#111"/>',
        'tree'     => '<rect x="21" y="28" width="6" height="16" rx="1.5" fill="#8b5a2b"/><circle cx="24" cy="19" r="14" fill="#22a35a"/><circle cx="17" cy="22" r="7" fill="#1e9150"/>',
        'house'    => '<rect x="11" y="22" width="26" height="21" fill="#e8b26b"/><path d="M5 24 24 7l19 17z" fill="#c9483b"/><rect x="21" y="31" width="7" height="12" fill="#7a4b2a"/><rect x="14" y="27" width="5" height="5" fill="#bfe3ff"/>',
        'car'      => '<path d="M5 33v-7c0-2 1-4 4-4h4l5-8h12l6 8h4c3 0 4 2 4 4v7z" fill="#e5413b"/><path d="M19 16h5v6h-9zM26 16h4l4 6h-8z" fill="#cfeaff"/><circle cx="15" cy="34" r="5.5" fill="#1f2937"/><circle cx="33" cy="34" r="5.5" fill="#1f2937"/><circle cx="15" cy="34" r="2.2" fill="#a3aab5"/><circle cx="33" cy="34" r="2.2" fill="#a3aab5"/>',
        'sun'      => '<circle cx="24" cy="24" r="9.5" fill="#fbbf24"/><path d="M24 3v7M24 38v7M3 24h7M38 24h7M9 9l5 5M34 34l5 5M9 39l5-5M34 14l5-5" stroke="#fbbf24" stroke-width="3.2" stroke-linecap="round"/>',
        'star'     => '<path d="M24 6l4.4 11.9 12.7.5-10 7.9 3.5 12.3L24 31.5l-10.6 7.1 3.5-12.3-10-7.9 12.7-.5z" fill="#f6c343" stroke="#d9a21b" stroke-width="1.5" stroke-linejoin="round"/>',
        'heart'    => '<path d="M24 41C10 31 5 24 5 17a9.5 9.5 0 0 1 19-3 9.5 9.5 0 0 1 19 3c0 7-5 14-19 24z" fill="#e0455f"/>',
        'apple'    => '<path d="M24 16c-4-3-15-3-15 9 0 10 7 18 11 18 2 0 3-1 4-1s2 1 4 1c4 0 11-8 11-18 0-12-11-12-15-9z" fill="#d9443a"/><path d="M24 16c0-4 1-7 3-10" stroke="#6b4423" stroke-width="2.4" fill="none" stroke-linecap="round"/><ellipse cx="31" cy="9" rx="5" ry="2.6" transform="rotate(-25 31 9)" fill="#3aa655"/>',
        'umbrella' => '<path d="M5 25a19 17 0 0 1 38 0z" fill="#8b5cf6"/><path d="M24 25v13a4 4 0 0 1-8 0" stroke="#444" stroke-width="3" fill="none" stroke-linecap="round"/><path d="M24 6v3" stroke="#444" stroke-width="3" stroke-linecap="round"/>',
        'key'      => '<circle cx="14" cy="24" r="8" fill="none" stroke="#d4a017" stroke-width="5"/><path d="M21 21.5h22v5H21zM34 26h4v7h-4zM40 26h3v5h-3z" fill="#d4a017"/>',
        'flower'   => '<path d="M24 27v18" stroke="#2e9e4f" stroke-width="3" stroke-linecap="round"/><ellipse cx="18" cy="38" rx="5" ry="2.5" transform="rotate(30 18 38)" fill="#2e9e4f"/><g fill="#f472b6"><circle cx="24" cy="10.5" r="6.5"/><circle cx="31.2" cy="15.7" r="6.5"/><circle cx="28.4" cy="24.1" r="6.5"/><circle cx="19.6" cy="24.1" r="6.5"/><circle cx="16.8" cy="15.7" r="6.5"/></g><circle cx="24" cy="18" r="5" fill="#fcd34d"/>',
    ];

    /**
     * A small "tap the cat" check, always shown. Renders 8 pictures; which one
     * is right is kept only in the session (as a random token), never in the page.
     */
    public static function pictureCheck(string $form): string
    {
        $names = array_keys(self::PICTURES);
        shuffle($names);
        $names = array_slice($names, 0, 8);
        $answer = $names[random_int(0, 7)];
        $tiles = '';
        $correct = '';
        foreach ($names as $name) {
            $token = bin2hex(random_bytes(8));
            if ($name === $answer) {
                $correct = $token;
            }
            $tilt = random_int(-14, 14);
            $tiles .= '<label class="pc__tile"><input type="radio" name="pc_pick" value="' . $token . '">'
                . '<span class="pc__img"><svg viewBox="0 0 48 48" aria-hidden="true" style="transform:rotate(' . $tilt . 'deg)">'
                . self::PICTURES[$name] . '</svg></span></label>';
        }
        $_SESSION['_pc'][$form] = ['a' => $correct, 'e' => time() + 1800];
        return '<fieldset class="pc"><legend class="pc__q">Quick check: tap the <strong>' . e($answer) . '</strong></legend>'
            . '<div class="pc__grid">' . $tiles . '</div></fieldset>';
    }

    /** True when the visitor tapped the right picture. Each check works once. */
    public static function pictureOk(string $form): bool
    {
        $c = $_SESSION['_pc'][$form] ?? null;
        unset($_SESSION['_pc'][$form]);
        $pick = (string) ($_POST['pc_pick'] ?? '');
        return is_array($c) && time() <= (int) $c['e'] && $pick !== ''
            && hash_equals((string) $c['a'], $pick);
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
