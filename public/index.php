<?php
declare(strict_types=1);

/**
 * EMChat Media — front controller.
 *
 * On Namecheap: point the domain's document root here (public/), or upload the
 * contents of public/ into public_html and keep app/, config/, storage/,
 * database/ one level above the web root.
 *
 * If your host forces everything into public_html, set EMCHAT_APP_DIR below to
 * wherever you placed the app/ folder.
 */

// PHP's built-in dev server (`php -S ... public/index.php`) hands every request
// to this router script, including static assets — Apache never does this in
// production because .htaccess serves real files directly before PHP runs.
// Replicate that here for local dev only; no effect under Apache/production.
if (PHP_SAPI === 'cli-server') {
    $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $reqPath;
    if ($reqPath !== '/' && is_file($file)) {
        return false;
    }
}

$appDir = getenv('EMCHAT_APP_DIR') ?: null;
if ($appDir === null) {
    $appDir = dirname(__DIR__) . '/app';
    if (!is_file($appDir . '/bootstrap.php')) {
        // Some hosts give FTP/SFTP access only to the web-servable directory
        // itself, with nothing writable above it — fall back to an "app"
        // folder sitting right next to this file instead. The .htaccess
        // files inside app/, config/, storage/, database/ keep them from
        // being served even though they're technically inside the web root.
        $appDir = __DIR__ . '/app';
    }
}
define('EMCHAT_APP_DIR', $appDir);

$config = require EMCHAT_APP_DIR . '/bootstrap.php';

use App\Router;

$router = new Router();
require EMCHAT_APP_DIR . '/routes.php';

// Clear one-shot old-input after it has been shown once.
register_shutdown_function(static function (): void {
    if (\App\Request::method() === 'GET') {
        unset($_SESSION['_old']);
    }
});

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (\Throwable $e) {
    error_log('[EMChat] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (!empty($config['debug'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $e . "\n";
    } else {
        (new App\View())->render('errors/generic', [
            'status'  => 500,
            'message' => 'Something broke on our end. Please try again in a moment.',
        ], 'marketing');
    }
}
