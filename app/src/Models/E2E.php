<?php
declare(strict_types=1);

namespace App\Models;

use App\App;

/**
 * End-to-end encryption for one-to-one messages.
 *
 * The server only ever stores public keys, an encrypted backup of each
 * user's private keys (locked with their recovery PIN in the browser), and
 * encrypted message bodies ("envelopes"). It cannot decrypt any of them.
 *
 * Envelope format: e2e1:<senderKeyId>:<recipientKeyId>:<iv b64url>:<ciphertext b64url>
 */
final class E2E
{
    /** DER prefix of an uncompressed P-256 SubjectPublicKeyInfo (followed by 0x04 || X || Y). */
    private const SPKI_P256_PREFIX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';
    private const ENVELOPE_RE = '/^e2e1:(\d{1,18}):(\d{1,18}):([A-Za-z0-9_-]{16}):([A-Za-z0-9_-]{22,40000})$/';

    /** Is encrypted messaging switched on for this user? config 'e2e_users': 'all' | list of ids, '@usernames' and/or 'admins'. */
    public static function enabledFor(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        $setting = config('e2e_users', []);
        if ($setting === 'all') {
            return true;
        }
        $list = (array) $setting;
        if (in_array('admins', $list, true) && ($user['role'] ?? '') === 'admin') {
            return true;
        }
        if (in_array('@' . strtolower((string) ($user['username'] ?? '')), array_map('strtolower', array_filter($list, 'is_string')), true)) {
            return true;
        }
        return in_array((int) $user['id'], array_map('intval', array_filter($list, 'is_numeric')), true);
    }

    /** e2e1: original 1:1 text format. e2e2: current format (1:1 and groups, text + attachments). */
    public static function isEnvelope(string $body): bool
    {
        return str_starts_with($body, 'e2e1:') || str_starts_with($body, 'e2e2:');
    }

    /**
     * e2e2:<base64url(JSON)> with JSON {s: senderKeyId, w: {recipientKeyId: wrappedMessageKey}, iv, ct}.
     * The message key is wrapped separately for every recipient key (and the sender's own).
     * @return array{s:int,w:array<int,string>}|null
     */
    public static function parseEnvelope2(string $body): ?array
    {
        if (!str_starts_with($body, 'e2e2:') || strlen($body) > 60000) {
            return null;
        }
        $json = base64_decode(strtr(substr($body, 5), '-_', '+/'), true);
        $d = $json === false ? null : json_decode($json, true);
        if (!is_array($d) || !isset($d['s'], $d['w'], $d['iv'], $d['ct']) || !is_int($d['s']) || !is_array($d['w'])
            || !is_string($d['iv']) || !preg_match('/^[A-Za-z0-9_-]{16}$/', $d['iv'])
            || !is_string($d['ct']) || !preg_match('/^[A-Za-z0-9_-]{22,}$/', $d['ct'])
            || count($d['w']) < 1 || count($d['w']) > 120) {
            return null;
        }
        $w = [];
        foreach ($d['w'] as $kid => $wrap) {
            if (!ctype_digit((string) $kid) || !is_string($wrap) || !preg_match('/^[A-Za-z0-9_-]{16}\.[A-Za-z0-9_-]{40,80}$/', $wrap)) {
                return null;
            }
            $w[(int) $kid] = $wrap;
        }
        return ['s' => $d['s'], 'w' => $w];
    }

    /** v2 envelope: the sender key is the sender's, and every wrapped key belongs to someone in the chat. */
    public static function validEnvelope2(string $body, int $senderId, array $participantIds): bool
    {
        $e = self::parseEnvelope2($body);
        if (!$e) {
            return false;
        }
        $owners = self::ownersOf(array_merge([$e['s']], array_keys($e['w'])));
        if (($owners[$e['s']] ?? 0) !== $senderId) {
            return false;
        }
        $allowed = array_flip(array_map('intval', $participantIds));
        $others = 0;
        foreach (array_keys($e['w']) as $kid) {
            $owner = $owners[$kid] ?? 0;
            if (!isset($allowed[$owner])) {
                return false;
            }
            if ($owner !== $senderId) {
                $others++;
            }
        }
        return $others > 0;
    }

    /** @return int[] all key ids a user has ever had (to find their wrapped key in a message). */
    public static function keyIdsFor(int $userId): array
    {
        return array_map('intval', array_column(App::db()->all('SELECT id FROM e2e_keys WHERE user_id = ? ORDER BY id', [$userId]), 'id'));
    }

    /** Everyone in a conversation, with their current key if they have one and encryption is on for them. */
    public static function members(int $conversationId): array
    {
        $rows = App::db()->all(
            'SELECT u.* FROM conversation_participants cp JOIN users u ON u.id = cp.user_id WHERE cp.conversation_id = ?',
            [$conversationId]
        );
        $out = [];
        foreach ($rows as $u) {
            $out[] = [
                'user_id'  => (int) $u['id'],
                'username' => $u['username'],
                'key'      => self::enabledFor($u) ? self::currentKey((int) $u['id']) : null,
            ];
        }
        return $out;
    }

    /** @return array{0:int,1:int}|null [senderKeyId, recipientKeyId] */
    public static function parseEnvelope(string $body): ?array
    {
        if (!preg_match(self::ENVELOPE_RE, $body, $m)) {
            return null;
        }
        return [(int) $m[1], (int) $m[2]];
    }

    /** Envelope is well-formed and its keys belong to the sender and the recipient. */
    public static function validEnvelope(string $body, int $senderId, int $recipientId): bool
    {
        $ids = self::parseEnvelope($body);
        if (!$ids) {
            return false;
        }
        $owners = self::ownersOf($ids);
        return ($owners[$ids[0]] ?? 0) === $senderId && ($owners[$ids[1]] ?? 0) === $recipientId;
    }

    /** @param int[] $keyIds  @return array<int,int> keyId => userId */
    private static function ownersOf(array $keyIds): array
    {
        $keyIds = array_values(array_unique(array_map('intval', $keyIds)));
        $in = implode(',', array_fill(0, count($keyIds), '?'));
        $out = [];
        foreach (App::db()->all("SELECT id, user_id FROM e2e_keys WHERE id IN ($in)", $keyIds) as $r) {
            $out[(int) $r['id']] = (int) $r['user_id'];
        }
        return $out;
    }

    public static function validPublicKey(string $b64): bool
    {
        if (strlen($b64) > 200 || !preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $b64)) {
            return false;
        }
        $raw = base64_decode($b64, true);
        return $raw !== false && strlen($raw) === 91
            && bin2hex(substr($raw, 0, 26)) === self::SPKI_P256_PREFIX && $raw[26] === "\x04";
    }

    /** Current (newest) key for a user. */
    public static function currentKey(int $userId): ?array
    {
        $row = App::db()->first(
            'SELECT id, user_id, public_key, created_at FROM e2e_keys WHERE user_id = ? ORDER BY id DESC LIMIT 1',
            [$userId]
        );
        return $row ? ['id' => (int) $row['id'], 'user_id' => (int) $row['user_id'], 'public_key' => $row['public_key'], 'created_at' => $row['created_at']] : null;
    }

    /** @param int[] $ids  @return array<string,array> */
    public static function keysByIds(array $ids): array
    {
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $ids), fn ($x) => $x > 0))), 0, 100);
        if (!$ids) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $out = [];
        foreach (App::db()->all("SELECT id, user_id, public_key FROM e2e_keys WHERE id IN ($in)", $ids) as $r) {
            $out[(string) $r['id']] = ['user_id' => (int) $r['user_id'], 'public_key' => $r['public_key']];
        }
        return $out;
    }

    /** Register a new key for the user (automatic first setup, or starting fresh on a device). */
    public static function addKey(int $userId, string $publicKey): int
    {
        return App::db()->insert('e2e_keys', ['user_id' => $userId, 'public_key' => $publicKey]);
    }

    /**
     * Optional PIN-locked backup of the user's keys (like WhatsApp's encrypted backup).
     * Every backup is kept, so a PIN used before starting fresh can still restore older messages.
     */
    public static function saveBackup(int $userId, string $keyring, string $salt, int $iterations, bool $replace = false): void
    {
        $id = App::db()->insert('e2e_backups', ['user_id' => $userId, 'keyring' => $keyring, 'salt' => $salt, 'iterations' => $iterations]);
        if ($replace) {
            App::db()->run('DELETE FROM e2e_backups WHERE user_id = ? AND id < ?', [$userId, $id]);
        }
    }

    public static function deleteBackups(int $userId): void
    {
        App::db()->run('DELETE FROM e2e_backups WHERE user_id = ?', [$userId]);
    }

    public static function backupCount(int $userId): int
    {
        return (int) App::db()->column('SELECT COUNT(*) FROM e2e_backups WHERE user_id = ?', [$userId]);
    }

    /** @return array<int,array> all of a user's backups, newest first (capped). */
    public static function backups(int $userId): array
    {
        return App::db()->all(
            'SELECT keyring, salt, iterations FROM e2e_backups WHERE user_id = ? ORDER BY id DESC LIMIT 30',
            [$userId]
        );
    }

    public static function hasBackup(int $userId): bool
    {
        return App::db()->column('SELECT 1 FROM e2e_backups WHERE user_id = ? LIMIT 1', [$userId]) !== null;
    }
}
