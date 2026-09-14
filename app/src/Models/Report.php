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
}
