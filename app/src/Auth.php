<?php
declare(strict_types=1);

namespace App;

final class Auth
{
    private ?array $cache = null;
    private bool $loaded = false;

    public function __construct(private Database $db) {}

    public function user(): ?array
    {
        if ($this->loaded) {
            return $this->cache;
        }
        $this->loaded = true;
        $id = $_SESSION['uid'] ?? null;
        if (!$id) {
            return $this->cache = null;
        }
        $user = $this->db->first('SELECT * FROM users WHERE id = ?', [$id]);
        if (!$user) {
            unset($_SESSION['uid']);
            return $this->cache = null;
        }
        // Touch last_seen at most every 60s.
        if (($user['last_seen_at'] ?? null) === null || strtotime($user['last_seen_at']) < time() - 60) {
            $this->db->run('UPDATE users SET last_seen_at = NOW() WHERE id = ?', [$id]);
        }
        return $this->cache = $user;
    }

    public function id(): ?int
    {
        $user = $this->user();
        return $user ? (int) $user['id'] : null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = $userId;
        $this->loaded = false;
        $this->cache = null;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    /**
     * Create a magic-link token for the given email. Returns the raw token
     * (put in the URL) — only its hash is stored.
     */
    public function createLoginToken(string $email, string $ipBinary): string
    {
        $raw = bin2hex(random_bytes(32));
        $ttl = (int) App::config('login_token_ttl', 900);
        $this->db->insert('login_tokens', [
            'email'      => $email,
            'token_hash' => hash('sha256', $raw),
            'purpose'    => 'login',
            'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
            'created_ip' => $ipBinary !== '' ? $ipBinary : null,
        ]);
        return $raw;
    }

    /**
     * Consume a raw token. Returns the email on success, null on failure.
     */
    public function consumeLoginToken(string $raw): ?string
    {
        $row = $this->db->first(
            'SELECT * FROM login_tokens WHERE token_hash = ? LIMIT 1',
            [hash('sha256', $raw)]
        );
        if (!$row || $row['consumed_at'] !== null || strtotime($row['expires_at']) < time()) {
            return null;
        }
        $this->db->run('UPDATE login_tokens SET consumed_at = NOW() WHERE id = ?', [$row['id']]);
        // Invalidate other outstanding tokens for this email.
        $this->db->run(
            'UPDATE login_tokens SET consumed_at = NOW() WHERE email = ? AND consumed_at IS NULL',
            [$row['email']]
        );
        return $row['email'];
    }
}
