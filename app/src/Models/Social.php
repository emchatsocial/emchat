<?php
declare(strict_types=1);

namespace App\Models;

use App\App;

/** Follows, likes, blocks, mutes. */
final class Social
{
    /** @return string 'accepted'|'pending'|'none' */
    public static function follow(int $followerId, int $targetId): string
    {
        if ($followerId === $targetId) {
            return 'none';
        }
        $db = App::db();
        if ($db->column('SELECT 1 FROM blocks WHERE blocker_id = ? AND blocked_id = ?', [$targetId, $followerId])) {
            return 'none';
        }
        $target = User::find($targetId);
        if (!$target) {
            return 'none';
        }
        $status = $target['is_private'] ? 'pending' : 'accepted';
        $db->run(
            'INSERT INTO follows (follower_id, followee_id, status) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE status = status',
            [$followerId, $targetId, $status]
        );
        $current = $db->column(
            'SELECT status FROM follows WHERE follower_id = ? AND followee_id = ?', [$followerId, $targetId]
        );
        if ($current === 'accepted') {
            Notification::push($targetId, $followerId, 'follow', null);
        } elseif ($current === 'pending') {
            Notification::push($targetId, $followerId, 'follow_request', null);
        }
        return $current ?: 'none';
    }

    public static function unfollow(int $followerId, int $targetId): void
    {
        App::db()->run('DELETE FROM follows WHERE follower_id = ? AND followee_id = ?', [$followerId, $targetId]);
    }

    public static function approve(int $ownerId, int $requesterId): void
    {
        App::db()->run(
            "UPDATE follows SET status = 'accepted' WHERE follower_id = ? AND followee_id = ? AND status = 'pending'",
            [$requesterId, $ownerId]
        );
        Notification::push($requesterId, $ownerId, 'follow', null);
    }

    public static function pendingRequests(int $ownerId): array
    {
        return App::db()->all(
            "SELECT u.* FROM follows f JOIN users u ON u.id = f.follower_id
             WHERE f.followee_id = ? AND f.status = 'pending' ORDER BY f.created_at DESC",
            [$ownerId]
        );
    }

    public static function toggleLike(int $userId, int $postId): bool
    {
        $db = App::db();
        $exists = $db->column('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?', [$userId, $postId]);
        if ($exists) {
            $db->run('DELETE FROM likes WHERE user_id = ? AND post_id = ?', [$userId, $postId]);
            $db->run('UPDATE posts SET like_count = GREATEST(like_count - 1, 0) WHERE id = ?', [$postId]);
            return false;
        }
        $db->run('INSERT INTO likes (user_id, post_id) VALUES (?, ?)', [$userId, $postId]);
        $db->run('UPDATE posts SET like_count = like_count + 1 WHERE id = ?', [$postId]);
        $ownerId = (int) $db->column('SELECT user_id FROM posts WHERE id = ?', [$postId]);
        if ($ownerId && $ownerId !== $userId) {
            Notification::push($ownerId, $userId, 'like', $postId);
        }
        return true;
    }

    public static function block(int $blockerId, int $blockedId): void
    {
        if ($blockerId === $blockedId) {
            return;
        }
        $db = App::db();
        $db->run('INSERT IGNORE INTO blocks (blocker_id, blocked_id) VALUES (?, ?)', [$blockerId, $blockedId]);
        $db->run('DELETE FROM follows WHERE (follower_id = ? AND followee_id = ?) OR (follower_id = ? AND followee_id = ?)',
            [$blockerId, $blockedId, $blockedId, $blockerId]);
    }

    public static function unblock(int $blockerId, int $blockedId): void
    {
        App::db()->run('DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?', [$blockerId, $blockedId]);
    }

    public static function mute(int $muterId, int $mutedId): void
    {
        App::db()->run('INSERT IGNORE INTO mutes (muter_id, muted_id) VALUES (?, ?)', [$muterId, $mutedId]);
    }

    public static function unmute(int $muterId, int $mutedId): void
    {
        App::db()->run('DELETE FROM mutes WHERE muter_id = ? AND muted_id = ?', [$muterId, $mutedId]);
    }

    public static function blockedIds(int $userId): array
    {
        return array_map('intval', array_column(App::db()->all(
            'SELECT blocked_id FROM blocks WHERE blocker_id = ?
             UNION SELECT blocker_id FROM blocks WHERE blocked_id = ?', [$userId, $userId]
        ), 'blocked_id'));
    }

    public static function followingList(int $userId): array
    {
        return App::db()->all(
            "SELECT u.*, f.status FROM follows f JOIN users u ON u.id = f.followee_id
             WHERE f.follower_id = ? AND f.status = 'accepted' ORDER BY f.created_at DESC",
            [$userId]
        );
    }

    public static function followerList(int $userId): array
    {
        return App::db()->all(
            "SELECT u.* FROM follows f JOIN users u ON u.id = f.follower_id
             WHERE f.followee_id = ? AND f.status = 'accepted' ORDER BY f.created_at DESC",
            [$userId]
        );
    }
}
