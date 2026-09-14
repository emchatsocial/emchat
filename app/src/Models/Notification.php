<?php
declare(strict_types=1);

namespace App\Models;

use App\App;

final class Notification
{
    public static function push(int $userId, int $actorId, string $type, ?int $subjectId): void
    {
        if ($userId === $actorId) {
            return;
        }
        $db = App::db();
        // Collapse duplicate like/follow/message spam: same actor+subject within a
        // day bumps the existing notification back to unread instead of piling up
        // a new row per like, follow, or message.
        if (in_array($type, ['like', 'follow', 'message'], true)) {
            $recent = $db->column(
                'SELECT id FROM notifications WHERE user_id = ? AND actor_id = ? AND type = ?
                 AND (subject_id <=> ?) AND created_at > (NOW() - INTERVAL 1 DAY)',
                [$userId, $actorId, $type, $subjectId]
            );
            if ($recent) {
                $db->run('UPDATE notifications SET created_at = NOW(), read_at = NULL WHERE id = ?', [$recent]);
                return;
            }
        }
        $db->insert('notifications', [
            'user_id'    => $userId,
            'actor_id'   => $actorId,
            'type'       => $type,
            'subject_id' => $subjectId,
        ]);
    }

    public static function unreadCount(int $userId): int
    {
        return (int) App::db()->column(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$userId]
        );
    }

    public static function markAllRead(int $userId): void
    {
        App::db()->run('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL', [$userId]);
    }

    public static function list(int $userId, int $limit = 40): array
    {
        return App::db()->all(
            'SELECT n.*, u.username, u.display_name, u.avatar_path
             FROM notifications n JOIN users u ON u.id = n.actor_id
             WHERE n.user_id = ? ORDER BY n.id DESC LIMIT ?',
            [$userId, $limit]
        );
    }
}
