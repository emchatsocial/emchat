<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Request;
use App\Models\Post;
use App\Models\User;

final class FeedController extends Controller
{
    public function home(): void
    {
        $user = require_login();
        $before = Request::int('before') ?: null;
        $limit = 20;
        $posts = Post::timeline((int) $user['id'], $before, $limit);
        $next = count($posts) >= $limit ? (int) end($posts)['id'] : null;

        if (Request::wantsJson()) {
            json_response([
                'ok'   => true,
                'html' => $this->renderPosts($posts),
                'next' => $next,
            ]);
        }

        $suggestions = $this->suggestions((int) $user['id']);
        $this->render('feed', [
            'meta'        => $this->meta(['title' => 'Home — ' . config('app_name'), 'robots' => 'noindex,nofollow']),
            'posts'       => $posts,
            'next'        => $next,
            'suggestions' => $suggestions,
            'empty_hint'  => empty($posts) && $before === null,
        ]);
    }

    public function explore(): void
    {
        $viewer = current_user();
        $viewerId = $viewer ? (int) $viewer['id'] : null;
        $q = trim((string) Request::input('q', ''));
        $tag = trim((string) Request::input('tag', ''));

        $people = $q !== '' ? User::search($q, 12) : [];
        $posts = Post::explore($viewerId, $tag ?: null, 30);

        $title = $tag !== '' ? "#{$tag} · " . config('app_name')
            : ($q !== '' ? "Search: {$q} · " . config('app_name') : 'Explore: Public Posts on EMChat');

        $this->render('explore', [
            'meta' => $this->meta([
                'title'       => $title,
                'description' => 'Browse public posts and profiles on EMChat, shown in the order they were shared: no algorithm choosing what you see, no ads mixed in.',
                'canonical'   => url('/explore'),
                'robots'      => ($q !== '' || $tag !== '') ? 'noindex,follow' : 'index,follow',
            ]),
            'query'  => $q,
            'tag'    => $tag,
            'people' => $people,
            'posts'  => $posts,
        ], $viewer ? 'app' : 'marketing');
    }

    private function renderPosts(array $posts): string
    {
        $out = '';
        foreach ($posts as $post) {
            $out .= $this->view->partial('post', ['post' => $post, 'viewer' => current_user()]);
        }
        return $out;
    }

    private function suggestions(int $userId): array
    {
        return \App\App::db()->all(
            "SELECT u.* FROM users u
             WHERE u.id <> ? AND u.discoverable = 1 AND u.is_private = 0
               AND u.id NOT IN (SELECT followee_id FROM follows WHERE follower_id = ?)
               AND u.id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id = ?)
             ORDER BY (SELECT COUNT(*) FROM follows f WHERE f.followee_id = u.id) DESC, u.id DESC
             LIMIT 5",
            [$userId, $userId, $userId]
        );
    }
}
