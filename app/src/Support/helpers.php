<?php
declare(strict_types=1);

use App\App;

/** HTML-escape. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absolute URL for a path. */
function url(string $path = '/'): string
{
    $base = rtrim((string) App::config('app_url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

/** Media URL for a stored relative path. */
function media_url(?string $relative): string
{
    if (!$relative) {
        return '';
    }
    return rtrim((string) App::config('media_url', '/media'), '/') . '/' . ltrim($relative, '/');
}

function config(string $key, $default = null)
{
    return App::config($key, $default);
}

/** Per-request Content-Security-Policy nonce for the few inline <script> blocks. */
function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $token);
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['_old'][$key] ?? $default);
}

/**
 * Set a flash message (with type), or read+clear the pending one.
 * @return array{message:string,type:string}|null
 */
function flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['_flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $value = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return is_array($value) ? $value : ($value ? ['message' => $value, 'type' => 'success'] : null);
}

/** Redirect and stop. */
function redirect(string $path): never
{
    $target = preg_match('~^https?://~', $path) ? $path : url($path);
    header('Location: ' . $target, true, 302);
    exit;
}

function json_response($data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/** "3 minutes ago" style. */
function time_ago(string $datetime): string
{
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 45) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm';
    if ($diff < 86400) return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'd';
    return date('M j', $ts);
}

/**
 * Strip invisible/formatting Unicode characters that have no legitimate use
 * in a post, bio, or message: zero-width spaces/joiners, bidi direction
 * overrides (used to spoof how text reads), BOM, soft hyphen, and similar
 * Unicode "Cf" formatting characters. These are only ever used to hide text,
 * fake reading direction, or dodge keyword filters, never to communicate.
 */
function strip_invisible_chars(string $text): string
{
    $clean = preg_replace(
        '/[\x{00AD}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{2066}-\x{2069}\x{FEFF}]/u',
        '',
        $text
    );
    return $clean ?? $text;
}

/**
 * True if $text is dominated by one character (or a short repeating run)
 * hammered over and over, e.g. a wall of "aaaaaaaa..." — a common low-effort
 * flood/spam pattern that a plain character-count limit doesn't catch.
 */
function looks_like_flood(string $text): bool
{
    $stripped = preg_replace('/\s+/u', '', $text) ?? $text;
    $len = mb_strlen($stripped);
    if ($len < 30) {
        return false;
    }
    if (preg_match('/(.)\1{14,}/us', $stripped)) {
        return true; // same character 15+ times in a row
    }
    $counts = [];
    foreach (preg_split('//u', $stripped, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $ch) {
        $counts[$ch] = ($counts[$ch] ?? 0) + 1;
    }
    return $counts !== [] && (max($counts) / $len) > 0.7; // one character is 70%+ of the body
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_substr($part, 0, 1);
    }
    return mb_strtoupper($letters ?: '?');
}

/**
 * Render post/comment/bio text safely: escape, then linkify URLs, @mentions
 * and #hashtags. Returns HTML.
 */
function rich_text(string $text): string
{
    $html = e($text);
    $html = preg_replace_callback(
        '~\bhttps?://[^\s<]+~',
        static fn ($m) => '<a href="' . e($m[0]) . '" rel="nofollow noopener" target="_blank">'
            . e(preg_replace('~^https?://(www\.)?~', '', $m[0])) . '</a>',
        $html
    );
    $html = preg_replace_callback(
        '~(^|[^a-zA-Z0-9_/])@([a-zA-Z0-9_]{1,30})~',
        static fn ($m) => $m[1] . '<a href="' . e(url('@' . $m[2])) . '">@' . e($m[2]) . '</a>',
        $html
    );
    $html = preg_replace_callback(
        '~(^|\s)#([a-zA-Z0-9_]{1,40})~',
        static fn ($m) => $m[1] . '<a href="' . e(url('explore?tag=' . rawurlencode($m[2]))) . '">#' . e($m[2]) . '</a>',
        $html
    );
    return nl2br($html, false);
}

/** Current authenticated user array or null. */
function current_user(): ?array
{
    return App::auth()->user();
}

/** A request path that is safe to redirect back to (no host, no scheme). */
function safe_path(?string $path, string $fallback = '/feed'): string
{
    $path = (string) $path;
    // must be a site-relative path: single leading slash, no scheme, no "//host",
    // no backslash, no control characters or whitespace.
    if ($path === ''
        || $path[0] !== '/'
        || str_starts_with($path, '//')
        || str_contains($path, '\\')
        || str_contains($path, '://')
        || preg_match('/[\x00-\x20\x7f]/', $path)
    ) {
        return $fallback;
    }
    return $path;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        $_SESSION['_intended'] = safe_path($_SERVER['REQUEST_URI'] ?? '/feed');
        redirect('/login');
    }
    if ($user['suspended_at'] !== null) {
        // Clear just the auth marker (not a full logout()) so the session
        // survives long enough to carry this flash message to the next request.
        unset($_SESSION['uid']);
        flash('Your account has been suspended.', 'error');
        redirect('/login');
    }
    // Keep authenticated pages out of any shared/proxy cache and the
    // browser's disk cache (bootstrap.php disabled PHP's blanket no-store
    // so logged-out pages could use bfcache; this restores it just for
    // pages that actually show a signed-in user's own content).
    if (!headers_sent()) {
        header('Cache-Control: no-store, private');
    }
    return $user;
}

/** Like require_login(), but also requires the 'admin' role. */
function require_admin(): array
{
    $user = require_login();
    if (($user['role'] ?? 'user') !== 'admin') {
        abort(403, 'You don\'t have access to this page.');
    }
    return $user;
}

function abort(int $status, string $message = ''): never
{
    http_response_code($status);
    (new \App\View())->render('errors/generic', [
        'status'  => $status,
        'message' => $message ?: match ($status) {
            403 => 'You don\'t have access to this page.',
            404 => 'That page could not be found.',
            429 => 'Too many requests. Please slow down.',
            default => 'Something went wrong.',
        },
    ], 'marketing');
    exit;
}
