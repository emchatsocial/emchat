<?php
declare(strict_types=1);

/**
 * EMChat Media — application bootstrap.
 * Included by public/index.php and by CLI scripts.
 */

define('EMCHAT_START', microtime(true));
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);

// ---- Environment (.env) ---------------------------------------------------
require APP_PATH . '/env.php';
emchat_load_env(BASE_PATH . '/.env');

// ---- Config -----------------------------------------------------------------
$configFile = BASE_PATH . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Setup needed</title>'
       . '<body style="font:16px/1.5 system-ui;max-width:40rem;margin:10vh auto;padding:0 1rem">'
       . '<h1>EMChat Media isn\'t configured yet</h1>'
       . '<p>Copy <code>config/config.example.php</code> to <code>config/config.php</code> '
       . 'and fill it in, or open <code>/install.php</code> in your browser.</p>';
    exit;
}
$config = require $configFile;

// ---- Error handling --------------------------------------------------------
date_default_timezone_set('UTC');

error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');
@ini_set('error_log', ($config['storage_path'] ?? BASE_PATH . '/storage') . '/logs/php-error.log');

// ---- Last-resort error handler ---------------------------------------------
// Catches anything uncaught anywhere (e.g. the database being unreachable) so
// a real server file path and stack trace can never leak to a visitor, even
// if 'debug' is ever accidentally left on in production. Deliberately has no
// dependency on the DB, session, or View — those may be exactly what's down.
set_exception_handler(static function (\Throwable $e) use ($config) {
    error_log('[EMChat] Uncaught: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    if (!empty($config['debug'])) {
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
        return;
    }
    echo '<!doctype html><meta charset="utf-8"><title>Something went wrong</title>'
       . '<body style="font:16px/1.5 system-ui;max-width:40rem;margin:10vh auto;padding:0 1rem;text-align:center">'
       . '<h1>Something went wrong</h1>'
       . '<p>We\'re working on it. Please try again in a few minutes.</p>';
});

// ---- Autoloader (PSR-4: App\ => app/src/) ---------------------------------
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = APP_PATH . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_PATH . '/src/Support/helpers.php';
require APP_PATH . '/src/Support/icons.php';

// ---- Container-lite: shared singletons ------------------------------------
\App\App::boot($config);

// ---- Storage dirs --------------------------------------------------------
foreach (['logs', 'sessions', 'mail', 'cache'] as $dir) {
    $path = $config['storage_path'] . '/' . $dir;
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

// ---- Security headers (defense in depth; .htaccess sets these too) --------
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    $https = (parse_url($config['app_url'], PHP_URL_SCHEME) === 'https');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), interest-cohort=(), browsing-topics=()');
    $nonce = csp_nonce();
    header(
        "Content-Security-Policy: default-src 'self'; img-src 'self' data: blob: https://www.google-analytics.com https://*.google-analytics.com; media-src 'self' blob:; "
        . "style-src 'self' 'unsafe-inline'; script-src 'self' 'nonce-{$nonce}' https://www.googletagmanager.com; font-src 'self'; "
        . "connect-src 'self' https://www.google-analytics.com https://*.google-analytics.com https://*.analytics.google.com https://www.googletagmanager.com; "
        . "form-action 'self'; frame-ancestors 'self'; base-uri 'self'; object-src 'none'"
    );
    if ($https) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    header_remove('X-Powered-By');
}

// ---- Sessions -----------------------------------------------------------
if (PHP_SAPI !== 'cli') {
    $sessionPath = $config['storage_path'] . '/sessions';
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.sid_length', '48');
    $secure = (parse_url($config['app_url'], PHP_URL_SCHEME) === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('emchat_sess');
    session_start();
}

return $config;
