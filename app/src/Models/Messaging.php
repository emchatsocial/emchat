<?php
declare(strict_types=1);

namespace App\Models;

use App\App;

final class Messaging
{
    /** How long after sending a message the sender may still edit it (seconds). */
    public const EDIT_WINDOW = 5400; // 1 hour 30 minutes

    /** Find or create a 1:1 conversation. Returns conversation id or 0 if blocked. */
    public static function conversationWith(int $userId, int $otherId): int
    {
        if ($userId === $otherId) {
            return 0;
        }
        $db = App::db();
        if ($db->column('SELECT 1 FROM blocks WHERE (blocker_id=? AND blocked_id=?) OR (blocker_id=? AND blocked_id=?)',
            [$userId, $otherId, $otherId, $userId])) {
            return 0;
        }
        // A 1:1 conversation that BOTH users are in and that has exactly 2 participants.
        $existing = $db->column(
            'SELECT cp.conversation_id
             FROM conversation_participants cp
             WHERE cp.conversation_id IN (SELECT conversation_id FROM conversation_participants WHERE user_id = ?)
               AND cp.conversation_id IN (SELECT conversation_id FROM conversation_participants WHERE user_id = ?)
             GROUP BY cp.conversation_id
             HAVING COUNT(*) = 2
             ORDER BY cp.conversation_id ASC
             LIMIT 1',
            [$userId, $otherId]
        );
        if ($existing) {
            return (int) $existing;
        }
        return $db->transaction(function ($db) use ($userId, $otherId) {
            // Re-check inside the transaction to avoid a double-click race.
            $again = $db->column(
                'SELECT cp.conversation_id FROM conversation_participants cp
                 WHERE cp.conversation_id IN (SELECT conversation_id FROM conversation_participants WHERE user_id = ?)
                   AND cp.conversation_id IN (SELECT conversation_id FROM conversation_participants WHERE user_id = ?)
                 GROUP BY cp.conversation_id HAVING COUNT(*) = 2 LIMIT 1',
                [$userId, $otherId]
            );
            if ($again) {
                return (int) $again;
            }
            $cid = $db->insert('conversations', ['created_at' => date('Y-m-d H:i:s')]);
            // The initiator's thread is always active. The recipient only gets it
            // in their normal inbox if they already follow the initiator —
            // otherwise it waits in "Message requests".
            $recipientFollowsInitiator = (bool) $db->column(
                "SELECT 1 FROM follows WHERE follower_id = ? AND followee_id = ? AND status = 'accepted'",
                [$otherId, $userId]
            );
            $otherState = $recipientFollowsInitiator ? 'active' : 'request';
            $db->run(
                'INSERT INTO conversation_participants (conversation_id, user_id, state) VALUES (?, ?, ?), (?, ?, ?)',
                [$cid, $userId, 'active', $cid, $otherId, $otherState]
            );
            return $cid;
        });
    }

    /** This participant's inbox state for a conversation: 'active', 'request', or null. */
    public static function participantState(int $conversationId, int $userId): ?string
    {
        return App::db()->column(
            'SELECT state FROM conversation_participants WHERE conversation_id = ? AND user_id = ?',
            [$conversationId, $userId]
        );
    }

    /** Move a pending request into the normal inbox. */
    public static function acceptRequest(int $conversationId, int $userId): bool
    {
        $db = App::db();
        $affected = $db->run(
            "UPDATE conversation_participants SET state = 'active'
             WHERE conversation_id = ? AND user_id = ? AND state = 'request'",
            [$conversationId, $userId]
        )->rowCount();
        return $affected > 0;
    }

    /** Decline a request: for a 1:1 this removes the conversation entirely. */
    public static function declineRequest(int $conversationId, int $userId): bool
    {
        if (self::participantState($conversationId, $userId) !== 'request') {
            return false;
        }
        if (self::isGroup($conversationId)) {
            // Leave the group quietly; nothing is announced for a declined invite.
            App::db()->run('DELETE FROM conversation_participants WHERE conversation_id = ? AND user_id = ?',
                [$conversationId, $userId]);
            return true;
        }
        self::deleteConversation($conversationId);
        return true;
    }

    /**
     * "Delete chat" for one person: hide everything up to now and drop the thread
     * from their inbox. It reappears (with only new messages) if someone writes
     * again. When every remaining participant has cleared it, it is hard-deleted.
     */
    public static function clearChat(int $conversationId, int $userId): bool
    {
        $db = App::db();
        if (!self::isParticipant($conversationId, $userId)) {
            return false;
        }
        $db->run(
            'UPDATE conversation_participants SET cleared_at = NOW() WHERE conversation_id = ? AND user_id = ?',
            [$conversationId, $userId]
        );
        // Everyone gone / everyone cleared → nothing to keep.
        $liveParticipants = (int) $db->column(
            'SELECT COUNT(*) FROM conversation_participants WHERE conversation_id = ? AND cleared_at IS NULL',
            [$conversationId]
        );
        if ($liveParticipants === 0) {
            self::deleteConversation($conversationId);
        }
        return true;
    }

    /** Hard-delete a conversation and everything in it. */
    public static function deleteConversation(int $conversationId): void
    {
        $db = App::db();
        foreach ($db->all(
            'SELECT a.path FROM message_attachments a JOIN messages m ON m.id = a.message_id WHERE m.conversation_id = ?',
            [$conversationId]
        ) as $a) {
            @unlink(rtrim((string) App::config('media_path'), '/') . '/' . $a['path']);
        }
        $db->transaction(function ($db) use ($conversationId) {
            $db->run('DELETE a FROM message_attachments a JOIN messages m ON m.id = a.message_id WHERE m.conversation_id = ?', [$conversationId]);
            $db->run('DELETE h FROM message_hides h JOIN messages m ON m.id = h.message_id WHERE m.conversation_id = ?', [$conversationId]);
            $db->run('DELETE FROM messages WHERE conversation_id = ?', [$conversationId]);
            $db->run('DELETE FROM conversation_participants WHERE conversation_id = ?', [$conversationId]);
            $db->run("DELETE FROM notifications WHERE type = 'message' AND subject_id = ?", [$conversationId]);
            $db->run('DELETE FROM conversations WHERE id = ?', [$conversationId]);
        });
    }

    public static function isParticipant(int $conversationId, int $userId): bool
    {
        return App::db()->column(
            'SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND user_id = ?',
            [$conversationId, $userId]
        ) !== null;
    }

    /**
     * @param array<int,array> $attachments  each: kind,path,name,mime,size,width,height
     */
    public static function send(int $conversationId, int $senderId, string $body, array $attachments = [], ?int $replyToId = null): ?int
    {
        $body = strip_invisible_chars(trim($body));
        if (($body === '' && !$attachments) || !self::isParticipant($conversationId, $senderId)) {
            return null;
        }
        $db = App::db();
        // Only allow replying to a live message in the same conversation.
        if ($replyToId !== null) {
            $ok = $db->column(
                'SELECT 1 FROM messages WHERE id = ? AND conversation_id = ? AND deleted_at IS NULL',
                [$replyToId, $conversationId]
            );
            if (!$ok) {
                $replyToId = null;
            }
        }
        $id = $db->insert('messages', [
            'conversation_id' => $conversationId,
            'sender_id'       => $senderId,
            'reply_to_id'     => $replyToId,
            'body'            => mb_substr($body, 0, 4000),
        ]);
        foreach ($attachments as $a) {
            $db->insert('message_attachments', [
                'message_id' => $id,
                'kind'       => $a['kind'],
                'path'       => $a['path'],
                'name'       => mb_substr($a['name'] ?? '', 0, 190),
                'mime'       => $a['mime'] ?? '',
                'size'       => (int) ($a['size'] ?? 0),
                'width'      => (int) ($a['width'] ?? 0),
                'height'     => (int) ($a['height'] ?? 0),
            ]);
        }
        $db->run('UPDATE conversations SET last_message_at = NOW() WHERE id = ?', [$conversationId]);
        // Replying to a request accepts it.
        $db->run("UPDATE conversation_participants SET last_read_at = NOW(), state = 'active'
                  WHERE conversation_id = ? AND user_id = ?", [$conversationId, $senderId]);
        // Only notify people who have this thread in their normal inbox — a
        // pending message request stays silent until it is accepted.
        $others = $db->all(
            "SELECT user_id FROM conversation_participants
             WHERE conversation_id = ? AND user_id <> ? AND state = 'active'",
            [$conversationId, $senderId]
        );
        foreach ($others as $o) {
            Notification::push((int) $o['user_id'], $senderId, 'message', $conversationId);
        }
        return $id;
    }

    /** Conversations in the user's normal inbox. */
    public static function inbox(int $userId): array
    {
        return self::threadsForState($userId, 'active');
    }

    /** Pending message requests (people the user doesn't follow who messaged first). */
    public static function requests(int $userId): array
    {
        return self::threadsForState($userId, 'request');
    }

    /** How many pending message requests are waiting. */
    public static function requestCount(int $userId): int
    {
        return (int) App::db()->column(
            "SELECT COUNT(*) FROM conversation_participants WHERE user_id = ? AND state = 'request'",
            [$userId]
        );
    }

    private static function threadsForState(int $userId, string $state): array
    {
        return App::db()->all(
            "SELECT c.id, c.last_message_at, c.is_group, c.title, c.photo_path,
                    other.id AS other_id, other.username, other.display_name, other.avatar_path,
                    cp.role AS my_role,
                    (SELECT COUNT(*) FROM conversation_participants x WHERE x.conversation_id = c.id) AS member_count,
                    (SELECT CASE WHEN m.deleted_at IS NOT NULL THEN '' ELSE m.body END
                        FROM messages m WHERE m.conversation_id = c.id
                        AND m.id NOT IN (SELECT message_id FROM message_hides WHERE user_id = cp.user_id)
                        AND (cp.cleared_at IS NULL OR m.created_at > cp.cleared_at)
                        ORDER BY m.id DESC LIMIT 1) AS last_body,
                    (SELECT m.sender_id FROM messages m WHERE m.conversation_id = c.id
                        AND m.id NOT IN (SELECT message_id FROM message_hides WHERE user_id = cp.user_id)
                        AND (cp.cleared_at IS NULL OR m.created_at > cp.cleared_at)
                        ORDER BY m.id DESC LIMIT 1) AS last_sender_id,
                    (SELECT m.kind FROM messages m WHERE m.conversation_id = c.id
                        AND m.id NOT IN (SELECT message_id FROM message_hides WHERE user_id = cp.user_id)
                        AND (cp.cleared_at IS NULL OR m.created_at > cp.cleared_at)
                        ORDER BY m.id DESC LIMIT 1) AS last_kind,
                    (SELECT su.display_name FROM messages m JOIN users su ON su.id = m.sender_id
                        WHERE m.conversation_id = c.id
                        AND m.id NOT IN (SELECT message_id FROM message_hides WHERE user_id = cp.user_id)
                        AND (cp.cleared_at IS NULL OR m.created_at > cp.cleared_at)
                        ORDER BY m.id DESC LIMIT 1) AS last_sender_name,
                    cp.last_read_at,
                    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id
                        AND m.sender_id <> ? AND m.deleted_at IS NULL
                        AND (cp.cleared_at IS NULL OR m.created_at > cp.cleared_at)
                        AND (cp.last_read_at IS NULL OR m.created_at > cp.last_read_at)) AS unread
             FROM conversation_participants cp
             JOIN conversations c ON c.id = cp.conversation_id
             LEFT JOIN conversation_participants ocp
                    ON ocp.conversation_id = c.id AND ocp.user_id <> cp.user_id AND c.is_group = 0
             LEFT JOIN users other ON other.id = ocp.user_id
             WHERE cp.user_id = ? AND cp.state = ?
               AND (c.is_group = 1 OR other.id IS NOT NULL)
               AND (cp.cleared_at IS NULL OR c.last_message_at IS NULL OR c.last_message_at > cp.cleared_at)
             ORDER BY c.last_message_at IS NULL, c.last_message_at DESC, c.id DESC",
            [$userId, $userId, $state]
        );
    }

    public static function messages(int $conversationId, int $limit = 100, int $afterId = 0, int $viewerId = 0): array
    {
        $rows = App::db()->all(
            "SELECT m.*, u.username, u.display_name, u.avatar_path
             FROM messages m JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = ? AND m.id > ?
               AND m.id NOT IN (SELECT message_id FROM message_hides WHERE user_id = ?)
               AND m.created_at > COALESCE(
                     (SELECT cleared_at FROM conversation_participants WHERE conversation_id = m.conversation_id AND user_id = ?),
                     '1000-01-01 00:00:00')
             ORDER BY m.id DESC LIMIT ?",
            [$conversationId, $afterId, $viewerId, $viewerId, $limit]
        );
        return self::hydrate($rows);
    }

    /** Messages in this conversation edited or deleted since $sinceEpoch (for live sync). */
    public static function revisedSince(int $conversationId, int $viewerId, int $sinceEpoch): array
    {
        $since = date('Y-m-d H:i:s', max(0, $sinceEpoch));
        $rows = App::db()->all(
            'SELECT m.*, u.username, u.display_name, u.avatar_path
             FROM messages m JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = ?
               AND m.id NOT IN (SELECT message_id FROM message_hides WHERE user_id = ?)
               AND (m.edited_at > ? OR m.deleted_at > ?)
             ORDER BY m.id DESC LIMIT 50',
            [$conversationId, $viewerId, $since, $since]
        );
        return self::hydrate($rows);
    }

    public static function messageById(int $messageId, int $viewerId = 0): ?array
    {
        $row = App::db()->first(
            'SELECT m.*, u.username, u.display_name, u.avatar_path
             FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.id = ?',
            [$messageId]
        );
        if (!$row) {
            return null;
        }
        return self::hydrate([$row])[0] ?? null;
    }

    /** @param array<int,array> $rows */
    private static function hydrate(array $rows): array
    {
        if (!$rows) {
            return [];
        }
        $db = App::db();
        $ids = array_column($rows, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));

        $attach = $db->all("SELECT * FROM message_attachments WHERE message_id IN ($in) ORDER BY id", $ids);
        $byMsg = [];
        foreach ($attach as $a) {
            $byMsg[$a['message_id']][] = $a;
        }

        // Reply targets
        $replyIds = array_values(array_filter(array_map(fn ($r) => $r['reply_to_id'] ? (int) $r['reply_to_id'] : null, $rows)));
        $replies = [];
        if ($replyIds) {
            $rin = implode(',', array_fill(0, count($replyIds), '?'));
            foreach ($db->all(
                "SELECT m.id, m.body, m.deleted_at, u.display_name
                 FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.id IN ($rin)",
                $replyIds
            ) as $rr) {
                $replies[$rr['id']] = $rr;
            }
        }

        foreach ($rows as &$r) {
            $r['attachments'] = ($r['deleted_at'] ?? null) ? [] : ($byMsg[$r['id']] ?? []);
            $r['reply'] = ($r['reply_to_id'] && isset($replies[$r['reply_to_id']])) ? $replies[$r['reply_to_id']] : null;
        }
        return $rows;
    }

    public static function editMessage(int $messageId, int $userId, string $body): ?array
    {
        $body = strip_invisible_chars(trim($body));
        if ($body === '') {
            return null;
        }
        $db = App::db();
        $m = $db->first('SELECT * FROM messages WHERE id = ?', [$messageId]);
        if (!$m || (int) $m['sender_id'] !== $userId || $m['deleted_at'] !== null) {
            return null;
        }
        if (strtotime($m['created_at']) < time() - self::EDIT_WINDOW) {
            return null;
        }
        $db->run('UPDATE messages SET body = ?, edited_at = NOW() WHERE id = ?', [mb_substr($body, 0, 4000), $messageId]);
        return self::messageById($messageId, $userId);
    }

    /** Retract a message for both sides (sender only). Leaves a tombstone. */
    public static function deleteForEveryone(int $messageId, int $userId): bool
    {
        $db = App::db();
        $m = $db->first('SELECT * FROM messages WHERE id = ?', [$messageId]);
        if (!$m || (int) $m['sender_id'] !== $userId || $m['deleted_at'] !== null) {
            return false;
        }
        foreach ($db->all('SELECT path FROM message_attachments WHERE message_id = ?', [$messageId]) as $a) {
            @unlink(rtrim((string) App::config('media_path'), '/') . '/' . $a['path']);
        }
        $db->run('DELETE FROM message_attachments WHERE message_id = ?', [$messageId]);
        $db->run("UPDATE messages SET body = '', deleted_at = NOW() WHERE id = ?", [$messageId]);
        return true;
    }

    /** Hide a message from this user's view only. */
    public static function deleteForMe(int $messageId, int $userId): bool
    {
        $db = App::db();
        $conv = $db->column('SELECT conversation_id FROM messages WHERE id = ?', [$messageId]);
        if (!$conv || !self::isParticipant((int) $conv, $userId)) {
            return false;
        }
        $db->run('INSERT IGNORE INTO message_hides (message_id, user_id) VALUES (?, ?)', [$messageId, $userId]);
        return true;
    }

    public static function conversationOf(int $messageId): int
    {
        return (int) App::db()->column('SELECT conversation_id FROM messages WHERE id = ?', [$messageId]);
    }

    /** People this user can start / continue a conversation with. */
    public static function searchRecipients(int $userId, string $q = '', int $limit = 20): array
    {
        $params = [$userId, $userId, $userId];
        $where = '';
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $where = ' AND (u.username LIKE ? OR u.display_name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        } else {
            // default: people you follow or who follow you
            $where = " AND (u.id IN (SELECT followee_id FROM follows WHERE follower_id = ? AND status='accepted')
                          OR u.id IN (SELECT follower_id FROM follows WHERE followee_id = ? AND status='accepted'))";
            $params[] = $userId;
            $params[] = $userId;
        }
        $params[] = $limit;
        return App::db()->all(
            "SELECT u.* FROM users u
             WHERE u.id <> ?
               AND u.id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id = ?)
               AND u.id NOT IN (SELECT blocker_id FROM blocks WHERE blocked_id = ?)
               $where
             ORDER BY u.display_name LIMIT ?",
            $params
        );
    }

    /** For a 1:1 chat, when the other person last read it (for a "Seen" marker). */
    public static function readReceipt(int $conversationId, int $viewerId): ?string
    {
        return App::db()->column(
            'SELECT last_read_at FROM conversation_participants
             WHERE conversation_id = ? AND user_id <> ? ORDER BY last_read_at DESC LIMIT 1',
            [$conversationId, $viewerId]
        );
    }

    public static function markRead(int $conversationId, int $userId): void
    {
        App::db()->run(
            'UPDATE conversation_participants SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?',
            [$conversationId, $userId]
        );
    }

    public static function totalUnread(int $userId): int
    {
        return (int) App::db()->column(
            "SELECT COUNT(*) FROM messages m
             JOIN conversation_participants cp ON cp.conversation_id = m.conversation_id AND cp.user_id = ?
             WHERE m.sender_id <> ? AND m.deleted_at IS NULL AND cp.state = 'active'
               AND m.id NOT IN (SELECT message_id FROM message_hides WHERE user_id = ?)
               AND (cp.cleared_at IS NULL OR m.created_at > cp.cleared_at)
               AND (cp.last_read_at IS NULL OR m.created_at > cp.last_read_at)",
            [$userId, $userId, $userId]
        );
    }

    public static function other(int $conversationId, int $userId): ?array
    {
        return App::db()->first(
            'SELECT u.* FROM conversation_participants cp JOIN users u ON u.id = cp.user_id
             WHERE cp.conversation_id = ? AND cp.user_id <> ?',
            [$conversationId, $userId]
        );
    }

    // ---------------------------------------------------------------- groups

    public static function isGroup(int $conversationId): bool
    {
        return (int) App::db()->column('SELECT is_group FROM conversations WHERE id = ?', [$conversationId]) === 1;
    }

    public static function isAdmin(int $conversationId, int $userId): bool
    {
        return App::db()->column(
            "SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND user_id = ? AND role = 'admin'",
            [$conversationId, $userId]
        ) !== null;
    }

    /** @return array{id:int,is_group:int,title:string,photo_path:?string,created_by:?int,member_count:int}|null */
    public static function groupMeta(int $conversationId): ?array
    {
        $row = App::db()->first(
            'SELECT c.id, c.is_group, c.title, c.photo_path, c.created_by,
                    (SELECT COUNT(*) FROM conversation_participants x WHERE x.conversation_id = c.id) AS member_count
             FROM conversations c WHERE c.id = ? AND c.is_group = 1',
            [$conversationId]
        );
        return $row ?: null;
    }

    /** Members of a conversation, admins first then by join order. */
    public static function participants(int $conversationId): array
    {
        return App::db()->all(
            "SELECT u.id, u.username, u.display_name, u.avatar_path, cp.role, cp.joined_at
             FROM conversation_participants cp JOIN users u ON u.id = cp.user_id
             WHERE cp.conversation_id = ?
             ORDER BY cp.role = 'admin' DESC, cp.joined_at ASC, u.id ASC",
            [$conversationId]
        );
    }

    /**
     * @param array<int,int> $memberIds
     */
    public static function createGroup(int $creatorId, string $title, array $memberIds): int
    {
        $title = strip_invisible_chars(trim($title));
        if ($title === '') {
            $title = 'New group';
        }
        $memberIds = array_values(array_unique(array_filter(array_map('intval', $memberIds), fn ($x) => $x > 0 && $x !== $creatorId)));

        $db = App::db();
        return $db->transaction(function ($db) use ($creatorId, $title, $memberIds) {
            $cid = $db->insert('conversations', [
                'is_group'        => 1,
                'title'           => mb_substr($title, 0, 80),
                'created_by'      => $creatorId,
                'created_at'      => date('Y-m-d H:i:s'),
                'last_message_at' => date('Y-m-d H:i:s'),
            ]);
            $db->run(
                "INSERT INTO conversation_participants (conversation_id, user_id, role, added_by) VALUES (?, ?, 'admin', ?)",
                [$cid, $creatorId, $creatorId]
            );
            // valid targets: real users not blocking / blocked by creator
            foreach ($memberIds as $uid) {
                $ok = $db->column(
                    'SELECT 1 FROM users u WHERE u.id = ?
                       AND u.id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id = ?)
                       AND u.id NOT IN (SELECT blocker_id FROM blocks WHERE blocked_id = ?)',
                    [$uid, $creatorId, $creatorId]
                );
                if ($ok) {
                    $state = self::follows($uid, $creatorId) ? 'active' : 'request';
                    $db->run(
                        "INSERT INTO conversation_participants (conversation_id, user_id, role, state, added_by) VALUES (?, ?, 'member', ?, ?)",
                        [$cid, $uid, $state, $creatorId]
                    );
                }
            }
            self::system($cid, $creatorId, 'created the group');
            return $cid;
        });
    }

    /** Does $a follow $b (accepted)? */
    private static function follows(int $a, int $b): bool
    {
        return (bool) App::db()->column(
            "SELECT 1 FROM follows WHERE follower_id = ? AND followee_id = ? AND status = 'accepted'",
            [$a, $b]
        );
    }

    /** Insert a system message (kind='system'); body is stored without the actor's name. */
    public static function system(int $conversationId, int $actorId, string $text): void
    {
        $db = App::db();
        $id = $db->insert('messages', [
            'conversation_id' => $conversationId,
            'sender_id'       => $actorId,
            'kind'            => 'system',
            'body'            => mb_substr(trim($text), 0, 300),
        ]);
        $db->run('UPDATE conversations SET last_message_at = NOW() WHERE id = ?', [$conversationId]);
        // keep the actor's own thread marked read
        $db->run('UPDATE conversation_participants SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?',
            [$conversationId, $actorId]);
    }

    /**
     * @param array<int,int> $userIds
     * @return int number added
     */
    public static function addMembers(int $conversationId, int $actorId, array $userIds): int
    {
        if (!self::isGroup($conversationId) || !self::isAdmin($conversationId, $actorId)) {
            return 0;
        }
        $db = App::db();
        $added = 0;
        foreach (array_unique(array_map('intval', $userIds)) as $uid) {
            if ($uid <= 0 || self::isParticipant($conversationId, $uid)) {
                continue;
            }
            $ok = $db->column(
                'SELECT display_name FROM users u WHERE u.id = ?
                   AND u.id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id = ?)
                   AND u.id NOT IN (SELECT blocker_id FROM blocks WHERE blocked_id = ?)',
                [$uid, $actorId, $actorId]
            );
            if (!$ok) {
                continue;
            }
            $state = self::follows($uid, $actorId) ? 'active' : 'request';
            $db->run(
                "INSERT INTO conversation_participants (conversation_id, user_id, role, state, added_by) VALUES (?, ?, 'member', ?, ?)",
                [$conversationId, $uid, $state, $actorId]
            );
            self::system($conversationId, $actorId, 'added ' . $ok);
            if ($state === 'active') {
                Notification::push($uid, $actorId, 'message', $conversationId);
            }
            $added++;
        }
        return $added;
    }

    public static function removeMember(int $conversationId, int $actorId, int $targetId): bool
    {
        if (!self::isGroup($conversationId) || !self::isAdmin($conversationId, $actorId) || $actorId === $targetId) {
            return false;
        }
        $db = App::db();
        $name = $db->column('SELECT display_name FROM users WHERE id = ?', [$targetId]);
        if (!$name || !self::isParticipant($conversationId, $targetId)) {
            return false;
        }
        $db->run('DELETE FROM conversation_participants WHERE conversation_id = ? AND user_id = ?', [$conversationId, $targetId]);
        self::system($conversationId, $actorId, 'removed ' . $name);
        return true;
    }

    public static function leaveGroup(int $conversationId, int $userId): bool
    {
        if (!self::isGroup($conversationId) || !self::isParticipant($conversationId, $userId)) {
            return false;
        }
        $db = App::db();
        return $db->transaction(function ($db) use ($conversationId, $userId) {
            self::system($conversationId, $userId, 'left the group');
            $db->run('DELETE FROM conversation_participants WHERE conversation_id = ? AND user_id = ?', [$conversationId, $userId]);
            // if no admins remain, promote the earliest-joined member
            $adminLeft = $db->column(
                "SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND role = 'admin' LIMIT 1",
                [$conversationId]
            );
            if (!$adminLeft) {
                $next = $db->column(
                    'SELECT user_id FROM conversation_participants WHERE conversation_id = ?
                     ORDER BY joined_at ASC, user_id ASC LIMIT 1',
                    [$conversationId]
                );
                if ($next) {
                    $db->run("UPDATE conversation_participants SET role = 'admin' WHERE conversation_id = ? AND user_id = ?",
                        [$conversationId, (int) $next]);
                    self::system($conversationId, (int) $next, 'is now an admin');
                }
            }
            return true;
        });
    }

    public static function setRole(int $conversationId, int $actorId, int $targetId, string $role): bool
    {
        $role = $role === 'admin' ? 'admin' : 'member';
        if (!self::isGroup($conversationId) || !self::isAdmin($conversationId, $actorId) || $actorId === $targetId) {
            return false;
        }
        $db = App::db();
        $name = $db->column('SELECT display_name FROM users WHERE id = ?', [$targetId]);
        if (!$name || !self::isParticipant($conversationId, $targetId)) {
            return false;
        }
        $db->run('UPDATE conversation_participants SET role = ? WHERE conversation_id = ? AND user_id = ?',
            [$role, $conversationId, $targetId]);
        self::system($conversationId, $actorId, $role === 'admin' ? 'made ' . $name . ' an admin' : 'removed ' . $name . ' as admin');
        return true;
    }

    public static function renameGroup(int $conversationId, int $actorId, string $title): bool
    {
        $title = strip_invisible_chars(trim($title));
        if ($title === '' || !self::isGroup($conversationId) || !self::isAdmin($conversationId, $actorId)) {
            return false;
        }
        App::db()->run('UPDATE conversations SET title = ? WHERE id = ?', [mb_substr($title, 0, 80), $conversationId]);
        self::system($conversationId, $actorId, 'changed the group name to ' . mb_substr($title, 0, 80));
        return true;
    }

    public static function setGroupPhoto(int $conversationId, int $actorId, string $path): bool
    {
        if (!self::isGroup($conversationId) || !self::isAdmin($conversationId, $actorId)) {
            return false;
        }
        $db = App::db();
        $old = $db->column('SELECT photo_path FROM conversations WHERE id = ?', [$conversationId]);
        $db->run('UPDATE conversations SET photo_path = ? WHERE id = ?', [$path, $conversationId]);
        if ($old && $old !== $path) {
            @unlink(rtrim((string) App::config('media_path'), '/') . '/' . $old);
        }
        self::system($conversationId, $actorId, 'changed the group photo');
        return true;
    }
}
