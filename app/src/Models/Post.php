<?php
declare(strict_types=1);

namespace App\Models;

use App\App;

final class Post
{
    private const SELECT = "
        SELECT p.*, u.username, u.display_name, u.avatar_path, u.is_private AS author_private,
               u.suspended_at AS author_suspended_at
        FROM posts p JOIN users u ON u.id = p.user_id ";

    public static function create(int $userId, string $body, string $visibility, ?int $replyToId = null): int
    {
        $db = App::db();
        $id = $db->insert('posts', [
            'user_id'     => $userId,
            'body'        => mb_substr($body, 0, 2000),
            'visibility'  => in_array($visibility, ['public', 'followers', 'private'], true) ? $visibility : 'public',
            'reply_to_id' => $replyToId,
        ]);
        if ($replyToId) {
            $db->run('UPDATE posts SET reply_count = reply_count + 1 WHERE id = ?', [$replyToId]);
            $parentOwner = (int) $db->column('SELECT user_id FROM posts WHERE id = ?', [$replyToId]);
            if ($parentOwner && $parentOwner !== $userId) {
                Notification::push($parentOwner, $userId, 'comment', $replyToId);
            }
        }
        self::notifyMentions($id, $userId, $body);
        return $id;
    }

    private static function notifyMentions(int $postId, int $authorId, string $body): void
    {
        if (!preg_match_all('/(?:^|[^a-zA-Z0-9_\/])@([a-zA-Z0-9_]{1,30})/', $body, $m)) {
            return;
        }
        foreach (array_unique($m[1]) as $handle) {
            $user = User::findByUsername($handle);
            if ($user && (int) $user['id'] !== $authorId) {
                Notification::push((int) $user['id'], $authorId, 'mention', $postId);
            }
        }
    }

    public static function attachMedia(int $postId, array $media): void
    {
        foreach (array_values($media) as $i => $item) {
            App::db()->insert('post_media', [
                'post_id'  => $postId,
                'path'     => $item['path'],
                'width'    => $item['width'] ?? 0,
                'height'   => $item['height'] ?? 0,
                'alt'      => mb_substr($item['alt'] ?? '', 0, 280),
                'position' => $i,
            ]);
        }
    }

    public static function find(int $id, ?int $viewerId = null): ?array
    {
        $post = App::db()->first(self::SELECT . 'WHERE p.id = ? AND p.deleted_at IS NULL', [$id]);
        if (!$post) {
            return null;
        }
        if (!self::visibleTo($post, $viewerId)) {
            return null;
        }
        return self::hydrate([$post], $viewerId)[0];
    }

    public static function visibleTo(array $post, ?int $viewerId): bool
    {
        $authorId = (int) $post['user_id'];
        if ($viewerId === $authorId) {
            return true;
        }
        if ($viewerId !== null) {
            $blocked = App::db()->column(
                'SELECT 1 FROM blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)',
                [$viewerId, $authorId, $authorId, $viewerId]
            );
            if ($blocked) {
                return false;
            }
        }
        $vis = $post['visibility'];
        if ($vis === 'private') {
            return false;
        }
        // Public posts are visible to anyone, even on a private account.
        // A private account only restricts its followers-only posts and its
        // follower/following lists.
        if ($vis === 'public') {
            return true;
        }
        // followers-only: viewer must be an accepted follower
        if ($viewerId === null) {
            return false;
        }
        return App::db()->column(
            "SELECT 1 FROM follows WHERE follower_id = ? AND followee_id = ? AND status = 'accepted'",
            [$viewerId, $authorId]
        ) !== null;
    }

    /** @param array<int,array> $posts */
    private static function hydrate(array $posts, ?int $viewerId): array
    {
        if (!$posts) {
            return [];
        }
        $db = App::db();
        $ids = array_column($posts, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));

        $mediaRows = $db->all("SELECT * FROM post_media WHERE post_id IN ($in) ORDER BY position", $ids);
        $mediaByPost = [];
        foreach ($mediaRows as $m) {
            $mediaByPost[$m['post_id']][] = $m;
        }

        $liked = [];
        $following = [];
        if ($viewerId !== null) {
            $likedRows = $db->all("SELECT post_id FROM likes WHERE user_id = ? AND post_id IN ($in)", array_merge([$viewerId], $ids));
            $liked = array_flip(array_column($likedRows, 'post_id'));

            $authorIds = array_values(array_unique(array_column($posts, 'user_id')));
            if ($authorIds) {
                $ain = implode(',', array_fill(0, count($authorIds), '?'));
                $folRows = $db->all(
                    "SELECT followee_id FROM follows WHERE follower_id = ? AND status = 'accepted' AND followee_id IN ($ain)",
                    array_merge([$viewerId], $authorIds)
                );
                $following = array_flip(array_column($folRows, 'followee_id'));
            }
        }

        foreach ($posts as &$post) {
            $post['media'] = $mediaByPost[$post['id']] ?? [];
            $post['liked_by_viewer'] = isset($liked[$post['id']]);
            $post['body_html'] = rich_text($post['body']);
            $post['author_follow_status'] = isset($following[$post['user_id']]) ? 'accepted' : null;
        }
        return $posts;
    }

    /**
     * Home timeline: own posts + accepted-follow authors (chronological).
     * Respects mutes/blocks. Public posts from accounts the viewer doesn't
     * follow are never blended in here; visibility='public' only ever means
     * "visible on the author's own profile to anyone", not "surfaced to
     * people who didn't seek it out".
     */
    public static function timeline(int $viewerId, ?int $beforeId = null, int $limit = 20): array
    {
        $params = [$viewerId, $viewerId, $viewerId, $viewerId];
        $before = '';
        if ($beforeId) {
            $before = ' AND p.id < ?';
            $params[] = $beforeId;
        }
        $params[] = $limit;

        $rows = App::db()->all(self::SELECT . "
            WHERE p.deleted_at IS NULL AND p.reply_to_id IS NULL
              AND (
                    p.user_id = ?
                 OR p.user_id IN (SELECT followee_id FROM follows WHERE follower_id = ? AND status = 'accepted')
              )
              AND p.user_id NOT IN (SELECT muted_id FROM mutes WHERE muter_id = ?)
              AND p.user_id NOT IN (
                    SELECT blocked_id FROM blocks WHERE blocker_id = ?
              )
              AND (p.visibility <> 'private')
              AND u.suspended_at IS NULL
              $before
            ORDER BY p.id DESC LIMIT ?", $params);

        return self::hydrate($rows, $viewerId);
    }

    public static function byUser(int $authorId, ?int $viewerId, bool $includeReplies = false, int $limit = 30, ?int $beforeId = null): array
    {
        $params = [$authorId];
        $sql = self::SELECT . 'WHERE p.user_id = ? AND p.deleted_at IS NULL';
        if (!$includeReplies) {
            $sql .= ' AND p.reply_to_id IS NULL';
        }
        if ($viewerId !== $authorId) {
            $sql .= ' AND u.suspended_at IS NULL';
            if ($viewerId === null) {
                $sql .= " AND p.visibility = 'public'";
            } else {
                // Public posts for everyone; followers-only posts for accepted followers.
                $sql .= " AND (p.visibility = 'public' OR (p.visibility = 'followers' AND EXISTS (
                            SELECT 1 FROM follows f WHERE f.follower_id = ? AND f.followee_id = p.user_id AND f.status = 'accepted'
                         )))";
                $params[] = $viewerId;
            }
        }
        if ($beforeId) {
            $sql .= ' AND p.id < ?';
            $params[] = $beforeId;
        }
        $params[] = $limit;
        $sql .= ' ORDER BY p.id DESC LIMIT ?';
        return self::hydrate(App::db()->all($sql, $params), $viewerId);
    }

    /**
     * Posts shown to logged-out visitors on Explore: only the site owner's
     * (admin accounts) top-level Public posts, so nothing unmoderated appears.
     */
    public static function featured(int $limit = 10): array
    {
        $sql = self::SELECT . "WHERE u.role = 'admin' AND u.suspended_at IS NULL
                 AND p.deleted_at IS NULL AND p.reply_to_id IS NULL AND p.visibility = 'public'
                 ORDER BY p.id DESC LIMIT ?";
        return self::hydrate(App::db()->all($sql, [$limit]), null);
    }

    public static function replies(int $postId, ?int $viewerId, int $limit = 100): array
    {
        $rows = App::db()->all(
            self::SELECT . 'WHERE p.reply_to_id = ? AND p.deleted_at IS NULL AND u.suspended_at IS NULL ORDER BY p.id ASC LIMIT ?',
            [$postId, $limit]
        );
        $rows = array_values(array_filter($rows, static fn ($r) => self::visibleTo($r, $viewerId)));
        return self::hydrate($rows, $viewerId);
    }

    public static function update(int $postId, int $userId, string $body, ?string $visibility = null): ?array
    {
        $db = App::db();
        $post = $db->first('SELECT * FROM posts WHERE id = ? AND deleted_at IS NULL', [$postId]);
        if (!$post || (int) $post['user_id'] !== $userId) {
            return null;
        }
        $body = mb_substr(strip_invisible_chars(trim($body)), 0, 2000);
        if ($body === '' && !$db->column('SELECT 1 FROM post_media WHERE post_id = ?', [$postId])) {
            return null;
        }
        $vis = in_array($visibility, ['public', 'followers', 'private'], true) ? $visibility : $post['visibility'];
        $db->run('UPDATE posts SET body = ?, visibility = ?, edited_at = NOW() WHERE id = ?', [$body, $vis, $postId]);
        self::notifyMentions($postId, $userId, $body);
        return self::find($postId, $userId);
    }

    public static function delete(int $postId, int $userId): bool
    {
        $post = App::db()->first('SELECT * FROM posts WHERE id = ? AND deleted_at IS NULL', [$postId]);
        if (!$post || (int) $post['user_id'] !== $userId) {
            return false;
        }
        App::db()->run('UPDATE posts SET deleted_at = NOW() WHERE id = ?', [$postId]);
        if ($post['reply_to_id']) {
            App::db()->run('UPDATE posts SET reply_count = GREATEST(reply_count - 1, 0) WHERE id = ?', [$post['reply_to_id']]);
        }
        return true;
    }

    /** Moderator deletion: skips the ownership check delete() enforces. */
    public static function adminDelete(int $postId): bool
    {
        $post = App::db()->first('SELECT * FROM posts WHERE id = ? AND deleted_at IS NULL', [$postId]);
        if (!$post) {
            return false;
        }
        App::db()->run('UPDATE posts SET deleted_at = NOW() WHERE id = ?', [$postId]);
        if ($post['reply_to_id']) {
            App::db()->run('UPDATE posts SET reply_count = GREATEST(reply_count - 1, 0) WHERE id = ?', [$post['reply_to_id']]);
        }
        return true;
    }

    public static function countAll(): int
    {
        return (int) App::db()->column('SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL');
    }

    public static function likers(int $postId): array
    {
        return App::db()->all(
            'SELECT u.* FROM likes l JOIN users u ON u.id = l.user_id WHERE l.post_id = ? ORDER BY l.created_at DESC',
            [$postId]
        );
    }

}
