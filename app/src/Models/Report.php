<?php
declare(strict_types=1);

namespace App\Models;

use App\App;

final class Report
{
    public const REASONS = [
        'spam'        => 'Spam or scam',
        'abuse'       => 'Harassment or hate',
        'nsfw'        => 'Sensitive or explicit content',
        'violence'    => 'Violence or threats',
        'impersonation' => 'Impersonation',
        'other'       => 'Something else',
    ];

    public static function file(int $reporterId, string $type, int $subjectId, string $reason, string $note = ''): void
    {
        if (!in_array($type, ['post', 'user', 'message'], true)) {
            return;
        }
        $reason = isset(self::REASONS[$reason]) ? $reason : 'other';
        App::db()->run(
            'INSERT INTO reports (reporter_id, subject_type, subject_id, reason, note)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE reason = VALUES(reason), note = VALUES(note), created_at = NOW()',
            [$reporterId, $type, $subjectId, $reason, mb_substr($note, 0, 500)]
        );
    }

    /** Open (unhandled) reports, newest first, with the reporter's identity attached. */
    public static function pending(int $limit = 50): array
    {
        return App::db()->all(
            'SELECT r.*, u.username AS reporter_username, u.display_name AS reporter_name
             FROM reports r JOIN users u ON u.id = r.reporter_id
             WHERE r.handled_at IS NULL
             ORDER BY r.id DESC LIMIT ?',
            [$limit]
        );
    }

    public static function countPending(): int
    {
        return (int) App::db()->column('SELECT COUNT(*) FROM reports WHERE handled_at IS NULL');
    }

    /** Mark a report handled, recording who handled it and what was done. */
    public static function resolve(int $id, int $adminId, string $action): bool
    {
        return App::db()->run(
            'UPDATE reports SET handled_at = NOW(), handled_by = ?, action = ? WHERE id = ? AND handled_at IS NULL',
            [$adminId, mb_substr($action, 0, 30), $id]
        )->rowCount() > 0;
    }

    public static function find(int $id): ?array
    {
        return App::db()->first('SELECT * FROM reports WHERE id = ?', [$id]);
    }
}
