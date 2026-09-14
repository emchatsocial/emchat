<?php
declare(strict_types=1);
/**
 * EMChat Media — browser installer.
 * Delete this file after setup (it refuses to run once config/config.php exists).
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
$configPath = $root . '/config/config.php';
$examplePath = $root . '/config/config.example.php';

if (is_file($configPath)) {
    http_response_code(403);
    exit('<p style="font:16px system-ui;margin:12vh auto;max-width:34rem">EMChat is already configured. Delete <code>public/install.php</code>.</p>');
}

$errors = [];
$done = false;

function req(string $k, string $d = ''): string { return trim((string) ($_POST[$k] ?? $d)); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appUrl = rtrim(req('app_url'), '/');
    $dbHost = req('db_host', 'localhost');
    $dbName = req('db_name');
    $dbUser = req('db_user');
    $dbPass = (string) ($_POST['db_pass'] ?? '');
    $mailDriver = req('mail_driver', 'log');
    $fromEmail = req('from_email');

    if (!filter_var($appUrl, FILTER_VALIDATE_URL)) $errors[] = 'Site URL is not valid.';
    if ($dbName === '' || $dbUser === '') $errors[] = 'Database name and user are required.';
    if ($fromEmail !== '' && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'From-email is not valid.';
    if (!extension_loaded('pdo_mysql')) $errors[] = 'PHP extension pdo_mysql is not enabled.';
    if (!extension_loaded('gd')) $errors[] = 'PHP extension gd is not enabled (needed for image uploads).';

    $pdo = null;
    if (!$errors) {
        try {
            $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (Throwable $e) {
            $errors[] = 'Could not connect to the database: ' . $e->getMessage();
        }
    }

    if (!$errors && $pdo) {
        try {
            $sql = file_get_contents($root . '/database/schema.sql');
            $pdo->exec($sql);
        } catch (Throwable $e) {
            $errors[] = 'Schema import failed: ' . $e->getMessage();
        }
    }

    if (!$errors) {
        $tpl = file_get_contents($examplePath);
        $key = bin2hex(random_bytes(24));
        $replacements = [
            "'https://emchat.social'" => var_export($appUrl, true),
            "'CHANGE_ME_TO_A_LONG_RANDOM_STRING_32_CHARS_MIN'" => var_export($key, true),
            "'driver'      => 'log'," => "'driver'      => " . var_export($mailDriver, true) . ",",
            "'host'    => 'localhost'," => "'host'    => " . var_export($dbHost, true) . ",",
            "'name'    => 'emchat'," => "'name'    => " . var_export($dbName, true) . ",",
            "'user'    => 'emchat'," => "'user'    => " . var_export($dbUser, true) . ",",
            "'pass'    => ''," => "'pass'    => " . var_export($dbPass, true) . ",",
            "'debug'        => false," => "'debug'        => false,",
        ];
        if ($fromEmail !== '') {
            $replacements["'hello@emchat.social'"] = var_export($fromEmail, true);
        }
        $out = strtr($tpl, $replacements);

        if (!is_dir($root . '/config')) @mkdir($root . '/config', 0775, true);
        if (@file_put_contents($configPath, $out) === false) {
            $errors[] = 'Could not write config/config.php — check folder permissions, or create it manually from the example.';
        } else {
            foreach (['logs', 'sessions', 'mail', 'cache'] as $d) @mkdir($root . '/storage/' . $d, 0775, true);
            foreach (['avatars', 'posts', 'messages', 'groups'] as $d) @mkdir($root . '/public/media/' . $d, 0775, true);
            $done = true;
        }
    }
}
?><!doctype html>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install EMChat Media</title>
<style>
body{font:15px/1.6 system-ui,sans-serif;max-width:38rem;margin:6vh auto;padding:0 1rem;color:#16172a;background:#f6f7fb}
h1{font-size:1.5rem}label{display:block;margin:.8rem 0 .2rem;font-weight:600}
input,select{width:100%;padding:.6rem;border:1px solid #d7d9e6;border-radius:8px;font:inherit}
button{margin-top:1.2rem;padding:.7rem 1.4rem;background:#5b5bd6;color:#fff;border:0;border-radius:999px;font:inherit;font-weight:600;cursor:pointer}
.err{background:#fdecec;border:1px solid #f2c4c4;padding:.6rem .9rem;border-radius:8px;margin:.4rem 0}
.ok{background:#e7f7ee;border:1px solid #b7e2c8;padding:1rem;border-radius:8px}
.row{display:flex;gap:.8rem}.row>*{flex:1}code{background:#ececf5;padding:.1rem .3rem;border-radius:4px}
small{color:#5c5f74}
</style>
<h1>Install EMChat Media</h1>

<?php if ($done): ?>
  <div class="ok">
    <h2>✓ Installed</h2>
    <p>Config written and database ready. Now:</p>
    <ol>
      <li><strong>Delete <code>public/install.php</code></strong> (or rename it).</li>
      <li>Visit <a href="/">your homepage</a> and create the first account.</li>
      <li>Set <code>'debug' =&gt; true</code> in <code>config/config.php</code> temporarily if email isn't set up — the sign-in link will show on screen.</li>
    </ol>
  </div>
<?php else: ?>
  <?php foreach ($errors as $e): ?><div class="err"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  <p><small>PHP <?= PHP_VERSION ?>. This creates <code>config/config.php</code> and imports the database schema.</small></p>
  <form method="post">
    <label>Site URL</label>
    <input name="app_url" value="<?= htmlspecialchars(req('app_url', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'emchat.social'))) ?>" required>
    <div class="row">
      <div><label>DB host</label><input name="db_host" value="<?= htmlspecialchars(req('db_host', 'localhost')) ?>"></div>
      <div><label>DB name</label><input name="db_name" value="<?= htmlspecialchars(req('db_name')) ?>" required></div>
    </div>
    <div class="row">
      <div><label>DB user</label><input name="db_user" value="<?= htmlspecialchars(req('db_user')) ?>" required></div>
      <div><label>DB password</label><input name="db_pass" type="password"></div>
    </div>
    <label>Outgoing mail</label>
    <select name="mail_driver">
      <option value="log">Log to file (set up email later)</option>
      <option value="mail">PHP mail()</option>
      <option value="smtp">SMTP (edit host/user in config afterwards)</option>
    </select>
    <label>From email <small>(optional now)</small></label>
    <input name="from_email" value="<?= htmlspecialchars(req('from_email')) ?>" placeholder="hello@emchat.social">
    <button type="submit">Run installer</button>
  </form>
<?php endif; ?>
