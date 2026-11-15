<?php
declare(strict_types=1);

namespace App\Controllers;

use App\App;
use App\Request;
use App\Models\E2E;
use App\Models\Messaging;

/** JSON endpoints for the browser's end-to-end encryption (see public/assets/js/e2e.js). */
final class E2EController extends Controller
{
    private function user(): array
    {
        $user = current_user();
        if (!$user) {
            json_response(['ok' => false, 'auth' => false], 401);
        }
        if (!E2E::enabledFor($user)) {
            json_response(['ok' => false, 'error' => 'Encrypted messaging is not available yet.'], 403);
        }
        return $user;
    }

    private static function validKeyring(string $keyring): bool
    {
        return strlen($keyring) <= 50000 && (bool) preg_match('/^[A-Za-z0-9_-]{16}:[A-Za-z0-9_-]{40,}$/', $keyring);
    }

    /** GET /x/e2e/state — this user's current public key and whether a PIN backup exists. */
    public function state(): void
    {
        $uid = (int) $this->user()['id'];
        $count = E2E::backupCount($uid);
        json_response([
            'ok'         => true,
            'key'        => E2E::currentKey($uid),
            'my_keys'    => E2E::keyIdsFor($uid),
            'has_backup' => $count > 0,
            'backups'    => $count,
        ]);
    }

    /** POST /x/e2e/setup — register this device's new key (automatic first time, or starting fresh). */
    public function setup(): void
    {
        $this->verifyCsrf();
        $uid = (int) $this->user()['id'];
        if (!App::limiter()->attempt('e2e-setup:' . $uid, 10, 86400)) {
            json_response(['ok' => false, 'error' => 'Too many resets today. Please try again tomorrow.'], 429);
        }
        $pub = (string) Request::input('public_key', '');
        if (!E2E::validPublicKey($pub)) {
            json_response(['ok' => false, 'error' => 'Invalid key data.'], 422);
        }
        json_response(['ok' => true, 'key_id' => E2E::addKey($uid, $pub)]);
    }

    /** GET /x/e2e/backup — all PIN-locked backups, to restore on a device with no other device to approve it. */
    public function backup(): void
    {
        $uid = (int) $this->user()['id'];
        if (!App::limiter()->attempt('e2e-backup:' . $uid, 30, 3600)) {
            json_response(['ok' => false, 'error' => 'Too many attempts. Please wait a while and try again.'], 429);
        }
        $list = E2E::backups($uid);
        if (!$list) {
            json_response(['ok' => false, 'error' => 'No backup found.'], 404);
        }
        // Every backup the user ever made; the browser tries the PIN on each and keeps all the keys it unlocks.
        json_response(['ok' => true, 'backups' => array_map(fn ($b) => [
            'keyring' => $b['keyring'], 'salt' => $b['salt'], 'iterations' => (int) $b['iterations'],
        ], $list)]);
    }

    /** POST /x/e2e/backup — save or replace the optional PIN-locked backup. */
    public function saveBackup(): void
    {
        $this->verifyCsrf();
        $uid = (int) $this->user()['id'];
        if (!App::limiter()->attempt('e2e-backup-save:' . $uid, 20, 86400)) {
            json_response(['ok' => false, 'error' => 'Too many changes today. Please try again tomorrow.'], 429);
        }
        $keyring = (string) Request::input('keyring', '');
        $salt    = (string) Request::input('salt', '');
        $iters   = Request::int('iterations');
        if (!self::validKeyring($keyring) || !preg_match('/^[A-Za-z0-9_-]{22,64}$/', $salt) || $iters < 100000 || $iters > 5000000) {
            json_response(['ok' => false, 'error' => 'Invalid backup data.'], 422);
        }
        // replace=1 (changing the PIN after proving the current one): older backups are removed,
        // so the old PIN stops working. Otherwise every backup is kept.
        E2E::saveBackup($uid, $keyring, $salt, $iters, Request::boolean('replace'));
        json_response(['ok' => true]);
    }

    /** POST /x/e2e/backup/delete — remove all of the user's backups. */
    public function deleteBackups(): void
    {
        $this->verifyCsrf();
        $uid = (int) $this->user()['id'];
        E2E::deleteBackups($uid);
        json_response(['ok' => true]);
    }

    /** GET /x/e2e/keys?ids=1,2 — public keys by id (needed to decrypt older messages). */
    public function keys(): void
    {
        $this->user();
        $ids = explode(',', (string) Request::input('ids', ''));
        json_response(['ok' => true, 'keys' => E2E::keysByIds($ids)]);
    }

    /** GET /x/e2e/members/{cid} — everyone in the chat (1:1 or group) with their current key, for encrypting to all of them. */
    public function members(array $params): void
    {
        $uid = (int) $this->user()['id'];
        $cid = (int) $params['cid'];
        if (!Messaging::isParticipant($cid, $uid)) {
            json_response(['ok' => false, 'error' => 'Unavailable.'], 404);
        }
        json_response(['ok' => true, 'members' => E2E::members($cid)]);
    }

    /** GET /x/e2e/peer/{cid} — the other person's current key in a one-to-one chat. */
    public function peer(array $params): void
    {
        $uid = (int) $this->user()['id'];
        $cid = (int) $params['cid'];
        if (!Messaging::isParticipant($cid, $uid) || Messaging::isGroup($cid)) {
            json_response(['ok' => false, 'error' => 'Unavailable.'], 404);
        }
        $other = Messaging::other($cid, $uid);
        $key = null;
        if ($other && E2E::enabledFor($other)) {
            $key = E2E::currentKey((int) $other['id']);
        }
        json_response(['ok' => true, 'key' => $key, 'username' => $other['username'] ?? '']);
    }
}
