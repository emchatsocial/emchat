<?php
declare(strict_types=1);

namespace App\Controllers;

use App\App;
use App\Request;
use App\Upload;
use App\Models\Messaging;
use App\Models\Notification;
use App\Models\User;

final class MessageController extends Controller
{
    public function inbox(): void
    {
        $user = require_login();
        $this->render('messages/index', [
            'meta'    => $this->meta(['title' => 'Messages — ' . config('app_name'), 'robots' => 'noindex,nofollow']),
            'threads' => Messaging::inbox((int) $user['id']),
            'active'  => null,
        ]);
    }

    public function requests(): void
    {
        $user = require_login();
        $uid  = (int) $user['id'];
        $this->render('messages/requests', [
            'meta'    => $this->meta(['title' => 'Message requests — ' . config('app_name'), 'robots' => 'noindex,nofollow']),
            'threads' => Messaging::inbox($uid),
            'requests' => Messaging::requests($uid),
            'active'  => 'requests',
        ]);
    }

    public function acceptRequest(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $uid  = (int) $user['id'];
        $cid  = (int) $params['id'];
        if (!Messaging::acceptRequest($cid, $uid)) {
            $this->fail('That request is no longer available.');
        }
        if (Request::wantsJson()) {
            json_response(['ok' => true, 'message' => 'Request accepted', 'redirect' => url('/messages/' . $cid)]);
        }
        flash('Message request accepted.', 'success');
        redirect('/messages/' . $cid);
    }

    public function declineRequest(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $uid  = (int) $user['id'];
        $cid  = (int) $params['id'];
        if (!Messaging::declineRequest($cid, $uid)) {
            $this->fail('That request is no longer available.');
        }
        if (Request::wantsJson()) {
            json_response(['ok' => true, 'message' => 'Request deleted', 'redirect' => url('/messages/requests')]);
        }
        flash('Message request deleted.', 'success');
        redirect('/messages/requests');
    }

    public function deleteChat(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $uid  = (int) $user['id'];
        $cid  = (int) $params['id'];
        if (!Messaging::clearChat($cid, $uid)) {
            $this->fail('This conversation is unavailable.');
        }
        if (Request::wantsJson()) {
            json_response(['ok' => true, 'message' => 'Chat deleted', 'redirect' => url('/messages')]);
        }
        flash('Chat deleted.', 'success');
        redirect('/messages');
    }

    public function compose(): void
    {
        $user = require_login();
        $q = trim((string) Request::input('q', ''));
        $people = Messaging::searchRecipients((int) $user['id'], $q, 25);

        if (Request::wantsJson()) {
            json_response([
                'ok'   => true,
                'html' => $this->view->partial('recipient_list', ['people' => $people]),
            ]);
        }
        $this->render('messages/new', [
            'meta'    => $this->meta(['title' => 'New message — ' . config('app_name'), 'robots' => 'noindex']),
            'threads' => Messaging::inbox((int) $user['id']),
            'people'  => $people,
            'query'   => $q,
            'active'  => 'new',
        ]);
    }

    public function start(array $params): void
    {
        $user = require_login();
        $other = User::findByUsername($params['username'] ?? '');
        if (!$other || $other['suspended_at'] !== null) {
            abort(404);
        }
        $cid = Messaging::conversationWith((int) $user['id'], (int) $other['id']);
        if (!$cid) {
            flash('You can\'t message that account.', 'error');
            redirect('/@' . $other['username']);
        }
        redirect("/messages/{$cid}");
    }

    public function thread(array $params): void
    {
        $user = require_login();
        $uid = (int) $user['id'];
        $cid = (int) $params['id'];
        if (!Messaging::isParticipant($cid, $uid)) {
            abort(404);
        }

        $isGroup = Messaging::isGroup($cid);

        // Incremental poll: ?after=<lastMessageId>&rev=<epoch>
        if (Request::wantsJson()) {
            $after = Request::int('after');
            if ($after > 0) {
                $fresh = array_reverse(Messaging::messages($cid, 60, $after, $uid));
                $rev = Request::int('rev');
                $revised = $rev > 0 ? Messaging::revisedSince($cid, $uid, $rev) : [];
                Messaging::markRead($cid, $uid);
                json_response([
                    'ok'    => true,
                    'now'   => time(),
                    'items' => array_map(fn ($m) => [
                        'id'   => (int) $m['id'],
                        'mine' => (int) $m['sender_id'] === $uid,
                        'html' => $this->view->partial('message_item', ['m' => $m, 'user' => $user, 'isGroup' => $isGroup]),
                    ], $fresh),
                    'revised' => array_map(fn ($m) => [
                        'id'   => (int) $m['id'],
                        'html' => $this->view->partial('message_item', ['m' => $m, 'user' => $user, 'isGroup' => $isGroup]),
                    ], $revised),
                ]);
            }
            $all = array_reverse(Messaging::messages($cid, 100, 0, $uid));
            Messaging::markRead($cid, $uid);
            json_response(['ok' => true, 'html' => $this->view->partial('messages_list', ['messages' => $all, 'user' => $user, 'isGroup' => $isGroup])]);
        }

        Messaging::markRead($cid, $uid);
        $messages = array_reverse(Messaging::messages($cid, 100, 0, $uid));
        $group = $isGroup ? Messaging::groupMeta($cid) : null;
        $other = $isGroup ? null : Messaging::other($cid, $uid);
        $isRequest = Messaging::participantState($cid, $uid) === 'request';
        $seenAt = (!$isGroup && !$isRequest) ? Messaging::readReceipt($cid, $uid) : null;

        $title = $isGroup
            ? (($group['title'] ?? 'Group') . ' — ' . config('app_name'))
            : ('Chat with @' . ($other['username'] ?? '') . ' — ' . config('app_name'));

        $this->render('messages/thread', [
            'meta'     => $this->meta(['title' => $title, 'robots' => 'noindex,nofollow']),
            'threads'  => Messaging::inbox($uid),
            'messages' => $messages,
            'other'    => $other,
            'group'    => $group,
            'is_group'   => $isGroup,
            'is_admin'   => $isGroup && Messaging::isAdmin($cid, $uid),
            'is_request' => $isRequest,
            'seen_at'  => $seenAt,
            'cid'      => $cid,
            'active'   => $isRequest ? 'requests' : $cid,
            'last_id'  => $messages ? (int) end($messages)['id'] : 0,
        ]);
    }

    // ---------------------------------------------------------------- groups

    public function newGroup(): void
    {
        $user = require_login();
        $people = Messaging::searchRecipients((int) $user['id'], '', 50);
        $this->render('messages/new_group', [
            'meta'    => $this->meta(['title' => 'New group — ' . config('app_name'), 'robots' => 'noindex']),
            'threads' => Messaging::inbox((int) $user['id']),
            'people'  => $people,
            'active'  => 'new',
        ]);
    }

    public function createGroup(): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $uid  = (int) $user['id'];

        $title   = trim((string) Request::input('title', ''));
        $members = (array) Request::raw('members', []);
        $members = array_slice(array_filter(array_map('intval', $members), fn ($x) => $x > 0), 0, 50);

        if (count($members) < 1) {
            $this->fail('Add at least one other person to the group.');
        }

        $cid = Messaging::createGroup($uid, $title, $members);
        if (Request::wantsJson()) {
            json_response(['ok' => true, 'redirect' => url('/messages/' . $cid)]);
        }
        redirect('/messages/' . $cid);
    }

    public function groupInfo(array $params): void
    {
        $user = require_login();
        $uid  = (int) $user['id'];
        $cid  = (int) $params['cid'];

        $group = Messaging::groupMeta($cid);
        if (!$group || !Messaging::isParticipant($cid, $uid)) {
            abort(404);
        }

        $isAdmin = Messaging::isAdmin($cid, $uid);
        $members = Messaging::participants($cid);
        $addable = $isAdmin ? $this->addableTo($cid, $uid) : [];

        $this->render('messages/group_info', [
            'meta'     => $this->meta(['title' => ($group['title'] ?: 'Group') . ' — ' . config('app_name'), 'robots' => 'noindex,nofollow']),
            'threads'  => Messaging::inbox($uid),
            'group'    => $group,
            'members'  => $members,
            'addable'  => $addable,
            'is_admin' => $isAdmin,
            'cid'      => $cid,
            'active'   => $cid,
        ]);
    }

    /** @return array<int,array> people the actor may add who aren't already in */
    private function addableTo(int $cid, int $uid): array
    {
        $people = Messaging::searchRecipients($uid, '', 50);
        $inIds = array_map(fn ($m) => (int) $m['id'], Messaging::participants($cid));
        return array_values(array_filter($people, fn ($p) => !in_array((int) $p['id'], $inIds, true)));
    }

    public function addMembers(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $uid  = (int) $user['id'];
        $cid  = (int) $params['cid'];

        $ids = array_filter(array_map('intval', (array) Request::raw('members', [])), fn ($x) => $x > 0);
        if (!$ids) {
            $this->fail('Pick someone to add.');
        }
        $n = Messaging::addMembers($cid, $uid, $ids);
        if (!$n) {
            $this->fail('Only group admins can add people.');
        }
        $this->groupDone($cid, $n === 1 ? 'Added 1 person.' : "Added {$n} people.");
    }

    public function removeMember(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $cid  = (int) $params['cid'];
        if (!Messaging::removeMember($cid, (int) $user['id'], (int) $params['uid'])) {
            $this->fail('Only group admins can remove people.');
        }
        $this->groupDone($cid, 'Member removed.');
    }

    public function setRole(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $cid  = (int) $params['cid'];
        $role = Request::input('role') === 'admin' ? 'admin' : 'member';
        if (!Messaging::setRole($cid, (int) $user['id'], (int) $params['uid'], $role)) {
            $this->fail('Only group admins can change roles.');
        }
        $this->groupDone($cid, $role === 'admin' ? 'Now an admin.' : 'Admin removed.');
    }

    public function renameGroup(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $cid  = (int) $params['cid'];
        if (!Messaging::renameGroup($cid, (int) $user['id'], (string) Request::input('title', ''))) {
            $this->fail('Only group admins can rename the group.');
        }
        $this->groupDone($cid, 'Group name updated.');
    }

    /** Success response for a group-management action: JSON for AJAX, redirect otherwise. */
    private function groupDone(int $cid, string $message): never
    {
        if (Request::wantsJson()) {
            json_response(['ok' => true, 'message' => $message, 'reload' => true]);
        }
        flash($message, 'success');
        redirect('/messages/' . $cid . '/info');
    }

    public function setPhoto(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $cid  = (int) $params['cid'];
        if (!Messaging::isAdmin($cid, (int) $user['id'])) {
            $this->fail('Only group admins can change the photo.');
        }
        $file = $_FILES['photo'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->fail('Choose an image.');
        }
        $res = \App\Image::process($file, 'groups', 512, 512);
        if (isset($res['error'])) {
            $this->fail($res['error']);
        }
        Messaging::setGroupPhoto($cid, (int) $user['id'], $res['path']);
        $this->groupDone($cid, 'Group photo updated.');
    }

    public function leaveGroup(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $cid  = (int) $params['cid'];
        if (!Messaging::leaveGroup($cid, (int) $user['id'])) {
            $this->fail('You are not in that group.');
        }
        if (Request::wantsJson()) {
            json_response(['ok' => true, 'message' => 'You left the group.', 'redirect' => url('/messages')]);
        }
        flash('You left the group.', 'success');
        redirect('/messages');
    }

    public function send(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $uid = (int) $user['id'];
        $cid = (int) $params['id'];

        if (!Messaging::isParticipant($cid, $uid)) {
            $this->fail('This conversation is unavailable.');
        }

        $body = trim((string) Request::input('body', ''));
        $replyTo = Request::int('reply_to') ?: null;
        $attachments = $this->intakeFiles();

        if ($body === '' && !$attachments) {
            $this->fail('Type a message or attach a file.');
        }
        if ($body !== '' && looks_like_flood($body)) {
            $this->fail('That looks like spam. Try writing something more varied.');
        }
        if (!App::limiter()->attempt('message:' . $uid, ...array_values(config('message_rate', ['max' => 60, 'per_seconds' => 300])))) {
            $this->fail('You\'re sending messages too fast. Please slow down.');
        }

        $id = Messaging::send($cid, $uid, $body, $attachments, $replyTo);
        if (!$id) {
            $this->fail('Message not sent.');
        }

        if (Request::wantsJson()) {
            $msg = Messaging::messageById($id, $uid);
            json_response([
                'ok'   => true,
                'id'   => $id,
                'html' => $msg ? $this->view->partial('message_item', ['m' => $msg, 'user' => $user]) : '',
            ]);
        }
        redirect("/messages/{$cid}");
    }

    public function editMessage(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $uid = (int) $user['id'];
        $mid = (int) $params['mid'];

        if (Messaging::conversationOf($mid) !== (int) $params['cid'] || !Messaging::isParticipant((int) $params['cid'], $uid)) {
            $this->fail('That message is unavailable.');
        }
        $editBody = (string) Request::input('body', '');
        if (looks_like_flood($editBody)) {
            $this->fail('That looks like spam. Try writing something more varied.');
        }
        $updated = Messaging::editMessage($mid, $uid, $editBody);
        if (!$updated) {
            $this->fail('This message can no longer be edited.');
        }
        json_response([
            'ok'   => true,
            'id'   => $mid,
            'html' => $this->view->partial('message_item', ['m' => $updated, 'user' => $user]),
        ]);
    }

    public function deleteMessage(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $uid = (int) $user['id'];
        $mid = (int) $params['mid'];
        $scope = Request::input('scope') === 'all' ? 'all' : 'me';

        if (Messaging::conversationOf($mid) !== (int) $params['cid'] || !Messaging::isParticipant((int) $params['cid'], $uid)) {
            $this->fail('That message is unavailable.');
        }

        if ($scope === 'all') {
            if (!Messaging::deleteForEveryone($mid, $uid)) {
                $this->fail('You can only delete your own messages for everyone.');
            }
            $fresh = Messaging::messageById($mid, $uid);
            json_response([
                'ok'    => true,
                'scope' => 'all',
                'id'    => $mid,
                'html'  => $fresh ? $this->view->partial('message_item', ['m' => $fresh, 'user' => $user]) : '',
            ]);
        }

        Messaging::deleteForMe($mid, $uid);
        json_response(['ok' => true, 'scope' => 'me', 'id' => $mid]);
    }

    public function reportMessage(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $uid = (int) $user['id'];
        $mid = (int) $params['mid'];

        if (Messaging::conversationOf($mid) !== (int) $params['cid'] || !Messaging::isParticipant((int) $params['cid'], $uid)) {
            $this->fail('That message is unavailable.');
        }
        \App\Models\Report::file(
            $uid, 'message', $mid,
            (string) Request::input('reason', 'other'),
            (string) Request::input('note', '')
        );
        json_response(['ok' => true]);
    }

    /** @return array<int,array> */
    private function intakeFiles(): array
    {
        $files = $_FILES['files'] ?? null;
        if (!$files || !is_array($files['name'] ?? null)) {
            return [];
        }
        $max = (int) config('max_attach_permsg', 6);
        $out = [];
        $count = min(count($files['name']), $max);
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $res = Upload::messageFile([
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ]);
            if (isset($res['error'])) {
                $this->fail($res['error']);
            }
            $out[] = $res;
        }
        return $out;
    }

    private function fail(string $message): never
    {
        if (Request::wantsJson()) {
            json_response(['ok' => false, 'error' => $message], 422);
        }
        flash($message, 'error');
        redirect('/messages');
    }
}
