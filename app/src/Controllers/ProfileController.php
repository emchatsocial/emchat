<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Request;
use App\Models\Post;
use App\Models\Social;
use App\Models\User;

final class ProfileController extends Controller
{
    public function show(array $params): void
    {
        $username = $params['username'] ?? '';
        $profile = User::findByUsername($username);
        if (!$profile) {
            abort(404, 'There is no @' . e($username) . ' here.');
        }
        $viewer = current_user();
        $viewerId = $viewer ? (int) $viewer['id'] : null;
        $isAdminViewer = ($viewer['role'] ?? 'user') === 'admin';

        if ($profile['suspended_at'] !== null) {
            $this->render('profile-suspended', [
                'meta'    => $this->meta(['title' => 'Account suspended · ' . config('app_name'), 'robots' => 'noindex,nofollow']),
                'profile' => $profile,
                'is_admin_viewer' => $isAdminViewer,
            ], $viewer ? 'app' : 'marketing');
            return;
        }

        $profile = User::withStats($profile, $viewerId);

        if (!empty($profile['blocks_you'])) {
            abort(404, 'There is no @' . e($username) . ' here.');
        }

        $tab = in_array(Request::input('tab'), ['replies', 'media'], true) ? Request::input('tab') : 'posts';
        $canView = User::canViewProfilePosts($profile, $viewerId);
        $posts = [];
        if ($canView) {
            $posts = Post::byUser((int) $profile['id'], $viewerId, $tab === 'replies', 30);
            if ($tab === 'media') {
                $posts = array_values(array_filter($posts, static fn ($p) => !empty($p['media'])));
            }
        }

        $indexable = !$profile['is_private'] && $profile['discoverable'];
        $desc = $profile['bio'] !== ''
            ? $profile['bio']
            : "{$profile['display_name']} (@{$profile['username']}) on " . config('app_name');

        $this->render('profile', [
            'meta' => $this->meta([
                'title'       => "{$profile['display_name']} (@{$profile['username']}) — " . config('app_name'),
                'description' => mb_substr($desc, 0, 200),
                'canonical'   => url('@' . $profile['username']),
                'robots'      => $indexable ? 'index,follow' : 'noindex,nofollow',
                'og_image'    => $profile['avatar_path'] ? url(media_url($profile['avatar_path'])) : url('/assets/img/og-default.png'),
                'type'        => 'profile',
                'jsonld'      => $indexable ? $this->personJsonLd($profile) : null,
            ]),
            'profile' => $profile,
            'posts'   => $posts,
            'tab'     => $tab,
            'can_view' => $canView,
            'viewer'  => $viewer,
        ], $viewer ? 'app' : 'marketing');
    }

    public function followers(array $params): void
    {
        $this->connections($params['username'] ?? '', 'followers');
    }

    public function following(array $params): void
    {
        $this->connections($params['username'] ?? '', 'following');
    }

    private function connections(string $username, string $kind): void
    {
        $profile = User::findByUsername($username);
        if (!$profile || $profile['suspended_at'] !== null) {
            abort(404);
        }
        $viewer = current_user();
        $viewerId = $viewer ? (int) $viewer['id'] : null;
        if (!User::canViewProfilePosts($profile, $viewerId)) {
            abort(403, 'This account is private.');
        }
        $people = $kind === 'followers'
            ? Social::followerList((int) $profile['id'])
            : Social::followingList((int) $profile['id']);
        $people = array_map(static fn ($p) => User::withStats($p, $viewerId), $people);

        $this->render('connections', [
            'meta'    => $this->meta([
                'title'  => ucfirst($kind) . " of @{$profile['username']} — " . config('app_name'),
                'robots' => 'noindex,follow',
            ]),
            'profile' => User::withStats($profile, $viewerId),
            'people'  => $people,
            'kind'    => $kind,
            'viewer'  => $viewer,
        ], $viewer ? 'app' : 'marketing');
    }

    /** Standalone shareable profile card (Open Graph rich preview). */
    public function card(array $params): void
    {
        $profile = User::findByUsername($params['username'] ?? '');
        if (!$profile || $profile['is_private'] || $profile['suspended_at'] !== null) {
            abort(404);
        }
        $viewer = current_user();
        $profile = User::withStats($profile, $viewer ? (int) $viewer['id'] : null);
        $this->render('card', [
            'meta' => $this->meta([
                'title'       => "{$profile['display_name']} (@{$profile['username']})",
                'description' => mb_substr($profile['bio'] ?: 'View this profile card on ' . config('app_name'), 0, 200),
                'canonical'   => url('@' . $profile['username'] . '/card'),
                'og_image'    => $profile['avatar_path'] ? url(media_url($profile['avatar_path'])) : url('/assets/img/og-default.png'),
            ]),
            'profile' => $profile,
            'viewer'  => $viewer,
        ], 'bare');
    }

    private function personJsonLd(array $p): string
    {
        return json_encode([
            '@context'    => 'https://schema.org',
            '@type'       => 'ProfilePage',
            'dateCreated' => date('c', strtotime($p['created_at'])),
            'mainEntity'  => array_filter([
                '@type'       => 'Person',
                'name'        => $p['display_name'],
                'alternateName' => '@' . $p['username'],
                'description' => $p['bio'] ?: null,
                'url'         => url('@' . $p['username']),
                'image'       => $p['avatar_path'] ? url(media_url($p['avatar_path'])) : null,
            ]),
        ]);
    }
}
