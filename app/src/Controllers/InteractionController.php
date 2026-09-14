<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Request;
use App\Models\Post;
use App\Models\Social;
use App\Models\User;

/** AJAX endpoints for likes, follows, follow-request approvals. */
final class InteractionController extends Controller
{
    public function like(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $postId = (int) $params['id'];
        $post = Post::find($postId, (int) $user['id']);
        if (!$post) {
            json_response(['ok' => false, 'error' => 'Post unavailable.'], 404);
        }
        $liked = Social::toggleLike((int) $user['id'], $postId);
        $count = (int) \App\App::db()->column('SELECT like_count FROM posts WHERE id = ?', [$postId]);
        $this->respond(['ok' => true, 'liked' => $liked, 'count' => $count], "/p/{$postId}");
    }

    public function follow(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $target = User::findByUsername($params['username'] ?? '');
        if (!$target) {
            json_response(['ok' => false, 'error' => 'No such account.'], 404);
        }
        if ($target['suspended_at'] !== null) {
            json_response(['ok' => false, 'error' => 'That account is unavailable.'], 404);
        }
        $status = Social::follow((int) $user['id'], (int) $target['id']);
        $this->respond(['ok' => true, 'status' => $status], '/@' . $target['username']);
    }

    public function unfollow(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $target = User::findByUsername($params['username'] ?? '');
        if ($target) {
            Social::unfollow((int) $user['id'], (int) $target['id']);
        }
        $this->respond(['ok' => true, 'status' => 'none'], '/@' . ($target['username'] ?? ''));
    }

    public function approve(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $requester = User::findByUsername($params['username'] ?? '');
        if ($requester) {
            Social::approve((int) $user['id'], (int) $requester['id']);
        }
        $this->respond(['ok' => true], '/notifications');
    }

    public function deny(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $requester = User::findByUsername($params['username'] ?? '');
        if ($requester) {
            Social::unfollow((int) $requester['id'], (int) $user['id']);
        }
        $this->respond(['ok' => true], '/notifications');
    }

    public function block(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $target = User::findByUsername($params['username'] ?? '');
        if ($target) {
            Social::block((int) $user['id'], (int) $target['id']);
        }
        flash('@' . ($target['username'] ?? '') . ' is blocked.');
        $this->respond(['ok' => true], '/@' . ($target['username'] ?? ''));
    }

    public function unblock(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $target = User::findByUsername($params['username'] ?? '');
        if ($target) {
            Social::unblock((int) $user['id'], (int) $target['id']);
        }
        $this->respond(['ok' => true], '/settings/privacy');
    }

    public function mute(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $target = User::findByUsername($params['username'] ?? '');
        if ($target) {
            Social::mute((int) $user['id'], (int) $target['id']);
        }
        $this->respond(['ok' => true], '/@' . ($target['username'] ?? ''));
    }

    public function unmute(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $target = User::findByUsername($params['username'] ?? '');
        if ($target) {
            Social::unmute((int) $user['id'], (int) $target['id']);
        }
        $this->respond(['ok' => true], '/@' . ($target['username'] ?? ''));
    }

    public function report(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $target = User::findByUsername($params['username'] ?? '');
        if ($target && (int) $target['id'] !== (int) $user['id']) {
            \App\Models\Report::file(
                (int) $user['id'], 'user', (int) $target['id'],
                (string) Request::input('reason', 'other'),
                (string) Request::input('note', '')
            );
        }
        flash('Thanks — our team will review this account.');
        $this->respond(['ok' => true], '/@' . ($target['username'] ?? ''));
    }

    private function respond(array $json, string $fallback): never
    {
        if (Request::wantsJson()) {
            json_response($json);
        }
        redirect($fallback);
    }
}
