<?php
declare(strict_types=1);

namespace App\Controllers;

use App\App;
use App\Image;
use App\Request;
use App\Models\Social;
use App\Models\User;

final class SettingsController extends Controller
{
    public function profile(): void
    {
        $user = require_login();
        $this->render('settings/profile', [
            'meta' => $this->meta(['title' => 'Edit profile — ' . config('app_name'), 'robots' => 'noindex']),
            'user' => $user,
            'section' => 'profile',
        ]);
    }

    public function updateProfile(): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $uid = (int) $user['id'];

        $displayName = mb_substr(strip_invisible_chars(trim((string) Request::input('display_name', ''))), 0, 60);
        $username = (string) Request::input('username', $user['username']);
        $bio = mb_substr(strip_invisible_chars(trim((string) Request::input('bio', ''))), 0, 280);
        $location = mb_substr(strip_invisible_chars(trim((string) Request::input('location', ''))), 0, 80);
        $website = mb_substr(trim((string) Request::input('website', '')), 0, 190);

        if ($website !== '' && !preg_match('~^https?://~i', $website)) {
            $website = 'https://' . $website;
        }
        if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
            $this->back('That website URL doesn\'t look right.', '/settings/profile');
        }
        if ($username !== $user['username']) {
            if (!User::validUsername($username)) {
                $this->back('Handles are 3–30 characters: letters, numbers, underscores.', '/settings/profile');
            }
            if (!User::usernameAvailable($username, $uid)) {
                $this->back('That handle is taken.', '/settings/profile');
            }
        }
        if ($displayName === '') {
            $displayName = $username;
        }

        $data = [
            'display_name' => $displayName,
            'username'     => $username,
            'bio'          => $bio,
            'location'     => $location,
            'website'      => $website,
        ];

        $avatarFile = $_FILES['avatar'] ?? null;
        if ($avatarFile && ($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $edge = (int) config('avatar_edge', 400);
            $res = Image::process($avatarFile, 'avatars', $edge, $edge);
            if (isset($res['error'])) {
                $this->back($res['error'], '/settings/profile');
            }
            if ($user['avatar_path']) {
                @unlink(rtrim((string) App::config('media_path'), '/') . '/' . $user['avatar_path']);
            }
            $data['avatar_path'] = $res['path'];
        }

        App::db()->update('users', $data, 'id = :id', ['id' => $uid]);

        if (Request::wantsJson()) {
            $fresh = User::find($uid);
            json_response([
                'ok'       => true,
                'message'  => 'Profile saved',
                'username' => $fresh['username'],
                'avatar'   => $fresh['avatar_path'] ? media_url($fresh['avatar_path']) : null,
            ]);
        }
        flash('Profile saved.');
        redirect('/@' . $data['username']);
    }

    public function removeAvatar(): void
    {
        $this->verifyCsrf();
        $user = require_login();
        if ($user['avatar_path']) {
            @unlink(rtrim((string) App::config('media_path'), '/') . '/' . $user['avatar_path']);
            App::db()->update('users', ['avatar_path' => null], 'id = :id', ['id' => $user['id']]);
        }
        if (Request::wantsJson()) {
            json_response(['ok' => true, 'message' => 'Photo removed', 'avatar' => null]);
        }
        flash('Profile photo removed.');
        redirect('/settings/profile');
    }

    public function privacy(): void
    {
        $user = require_login();
        $blocked = App::db()->all(
            'SELECT u.* FROM blocks b JOIN users u ON u.id = b.blocked_id WHERE b.blocker_id = ? ORDER BY b.created_at DESC',
            [$user['id']]
        );
        $muted = App::db()->all(
            'SELECT u.* FROM mutes m JOIN users u ON u.id = m.muted_id WHERE m.muter_id = ? ORDER BY m.created_at DESC',
            [$user['id']]
        );
        $this->render('settings/privacy', [
            'meta'    => $this->meta(['title' => 'Privacy & safety — ' . config('app_name'), 'robots' => 'noindex']),
            'user'    => $user,
            'blocked' => $blocked,
            'muted'   => $muted,
            'section' => 'privacy',
        ]);
    }

    public function updatePrivacy(): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $wasPrivate = (bool) $user['is_private'];
        $isPrivate = Request::boolean('is_private');

        App::db()->update('users', [
            'is_private'   => $isPrivate ? 1 : 0,
            'discoverable' => Request::boolean('discoverable') ? 1 : 0,
        ], 'id = :id', ['id' => $user['id']]);

        // Going public auto-accepts all pending requests.
        if ($wasPrivate && !$isPrivate) {
            App::db()->run("UPDATE follows SET status = 'accepted' WHERE followee_id = ? AND status = 'pending'", [$user['id']]);
        }
        if (Request::wantsJson()) {
            json_response(['ok' => true, 'message' => 'Saved', 'is_private' => $isPrivate]);
        }
        flash('Privacy settings saved.');
        redirect('/settings/privacy');
    }

    public function account(): void
    {
        $user = require_login();
        $this->render('settings/account', [
            'meta'    => $this->meta(['title' => 'Account — ' . config('app_name'), 'robots' => 'noindex']),
            'user'    => $user,
            'section' => 'account',
        ]);
    }

    /** GDPR-style data export as a JSON download. */
    public function exportData(): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $uid = (int) $user['id'];
        $db = App::db();

        $export = [
            'exported_at' => date('c'),
            'account'     => array_diff_key($user, ['id' => 1]),
            'posts'       => $db->all('SELECT id, body, visibility, created_at FROM posts WHERE user_id = ? AND deleted_at IS NULL', [$uid]),
            'comments'    => $db->all('SELECT id, post_id, body, created_at FROM comments WHERE user_id = ? AND deleted_at IS NULL', [$uid]),
            'likes'       => $db->all('SELECT post_id, created_at FROM likes WHERE user_id = ?', [$uid]),
            'following'   => array_column($db->all('SELECT followee_id FROM follows WHERE follower_id = ?', [$uid]), 'followee_id'),
            'followers'   => array_column($db->all('SELECT follower_id FROM follows WHERE followee_id = ?', [$uid]), 'follower_id'),
            'messages'    => $db->all(
                'SELECT m.conversation_id, m.body, m.created_at FROM messages m WHERE m.sender_id = ?', [$uid]
            ),
        ];

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="emchat-export-' . $user['username'] . '-' . date('Ymd') . '.json"');
        echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function deleteAccount(): void
    {
        $this->verifyCsrf();
        $user = require_login();
        if (Request::input('confirm') !== $user['username']) {
            $this->back('Type your handle exactly to confirm deletion.', '/settings/account');
        }
        $uid = (int) $user['id'];
        $db = App::db();
        $db->transaction(function ($db) use ($uid) {
            foreach ([
                'DELETE FROM likes WHERE user_id = ?',
                'DELETE FROM follows WHERE follower_id = ? OR followee_id = ?',
                'DELETE FROM blocks WHERE blocker_id = ? OR blocked_id = ?',
                'DELETE FROM mutes WHERE muter_id = ? OR muted_id = ?',
                'DELETE FROM notifications WHERE user_id = ? OR actor_id = ?',
                'DELETE FROM messages WHERE sender_id = ?',
                'DELETE FROM conversation_participants WHERE user_id = ?',
                'DELETE pm FROM post_media pm JOIN posts p ON p.id = pm.post_id WHERE p.user_id = ?',
                'DELETE FROM posts WHERE user_id = ?',
                'DELETE FROM login_tokens WHERE email = (SELECT email FROM users WHERE id = ?)',
                'DELETE FROM users WHERE id = ?',
            ] as $sql) {
                $count = substr_count($sql, '?');
                $db->run($sql, array_fill(0, $count, $uid));
            }
        });
        App::auth()->logout();
        redirect('/?deleted=1');
    }

    private function back(string $message, string $to): never
    {
        if (Request::wantsJson()) {
            json_response(['ok' => false, 'error' => $message], 422);
        }
        flash($message, 'error');
        redirect($to);
    }
}
