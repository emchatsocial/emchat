<?php
declare(strict_types=1);

namespace App\Models;

use App\App;

final class User
{
    public static function find(int $id): ?array
    {
        return App::db()->first('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        return App::db()->first('SELECT * FROM users WHERE username = ?', [$username]);
    }

    public static function findByEmail(string $email): ?array
    {
        return App::db()->first('SELECT * FROM users WHERE email = ?', [mb_strtolower($email)]);
    }

    public static function usernameAvailable(string $username, ?int $exceptId = null): bool
    {
        $row = App::db()->first(
            'SELECT id FROM users WHERE username = ? AND (? IS NULL OR id <> ?)',
            [$username, $exceptId, $exceptId]
        );
        return $row === null;
    }

    public static function validUsername(string $username): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)
            && !in_array(strtolower($username), self::RESERVED, true);
    }

    private const RESERVED = [
        'login', 'logout', 'feed', 'explore', 'settings', 'messages', 'notifications',
        'compose', 'admin', 'api', 'auth', 'about', 'privacy', 'terms', 'help', 'support',
        'p', 'u', 'x', 'media', 'assets', 'sitemap', 'robots', 'emchat', 'root', 'me', 'home',
    ];

    public static function create(string $email): int
    {
        $email = mb_strtolower(trim($email));
        $base = preg_replace('/[^a-z0-9_]/', '', strtolower(strstr($email . '@', '@', true))) ?: 'member';
        $base = substr($base, 0, 24);
        $username = $base;
        $i = 0;
        while (!self::usernameAvailable($username) || !self::validUsername($username)) {
            $username = substr($base, 0, 20) . ++$i;
        }
        return App::db()->insert('users', [
            'username'          => $username,
            'display_name'     => ucfirst($base),
            'email'            => $email,
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Adds follower/following/post counts + viewer-relationship flags. */
    public static function withStats(array $user, ?int $viewerId = null): array
    {
        $db = App::db();
        $user['followers_count'] = (int) $db->column(
            "SELECT COUNT(*) FROM follows WHERE followee_id = ? AND status = 'accepted'", [$user['id']]
        );
        $user['following_count'] = (int) $db->column(
            "SELECT COUNT(*) FROM follows WHERE follower_id = ? AND status = 'accepted'", [$user['id']]
        );
        $user['posts_count'] = (int) $db->column(
            'SELECT COUNT(*) FROM posts WHERE user_id = ? AND deleted_at IS NULL AND reply_to_id IS NULL', [$user['id']]
        );
        $user['is_self'] = $viewerId !== null && (int) $user['id'] === $viewerId;
        $user['follow_status'] = null;
        $user['blocked'] = false;
        if ($viewerId !== null && !$user['is_self']) {
            $user['follow_status'] = $db->column(
                'SELECT status FROM follows WHERE follower_id = ? AND followee_id = ?', [$viewerId, $user['id']]
            );
            $user['follows_you'] = $db->column(
                "SELECT status FROM follows WHERE follower_id = ? AND followee_id = ? AND status='accepted'",
                [$user['id'], $viewerId]
            ) !== null;
            $user['blocked'] = $db->column(
                'SELECT 1 FROM blocks WHERE blocker_id = ? AND blocked_id = ?', [$viewerId, $user['id']]
            ) !== null;
            $user['blocks_you'] = $db->column(
                'SELECT 1 FROM blocks WHERE blocker_id = ? AND blocked_id = ?', [$user['id'], $viewerId]
            ) !== null;
        }
        return $user;
    }

    public static function canViewProfilePosts(array $profile, ?int $viewerId): bool
    {
        if (!$profile['is_private']) {
            return true;
        }
        if ($viewerId === null) {
            return false;
        }
        if ((int) $profile['id'] === $viewerId) {
            return true;
        }
        return App::db()->column(
            "SELECT 1 FROM follows WHERE follower_id = ? AND followee_id = ? AND status = 'accepted'",
            [$viewerId, $profile['id']]
        ) !== null;
    }

    public static function search(string $query, int $limit = 20): array
    {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $query) . '%';
        return App::db()->all(
            "SELECT * FROM users
             WHERE discoverable = 1 AND (username LIKE ? OR display_name LIKE ?)
             ORDER BY (username = ?) DESC, id DESC LIMIT ?",
            [$like, $like, $query, $limit]
        );
    }
}
