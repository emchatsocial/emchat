<?php
declare(strict_types=1);

namespace App\Controllers;

use App\App;
use App\Image;
use App\Request;
use App\Models\Post;

final class PostController extends Controller
{
    public function store(): void
    {
        $this->verifyCsrf();
        $user = require_login();

        $body = strip_invisible_chars(trim((string) Request::input('body', '')));
        $visibility = (string) Request::input('visibility', 'public');
        $replyTo = Request::int('reply_to') ?: null;

        $media = $this->intakeImages();

        if ($body === '' && !$media) {
            $this->fail('Write something or add a photo.', $replyTo ? "/p/{$replyTo}" : '/feed');
        }
        if (mb_strlen($body) > 2000) {
            $this->fail('Posts are limited to 2000 characters.', '/feed');
        }
        if ($body !== '' && looks_like_flood($body)) {
            $this->fail('That looks like spam. Try writing something more varied.', $replyTo ? "/p/{$replyTo}" : '/feed');
        }
        if (!App::limiter()->attempt('post:' . $user['id'], ...array_values(config('post_rate', ['max' => 15, 'per_seconds' => 300])))) {
            $this->fail('You\'re posting too fast. Please slow down.', $replyTo ? "/p/{$replyTo}" : '/feed');
        }
        if ($replyTo && !Post::find($replyTo, (int) $user['id'])) {
            $this->fail('That post no longer exists.', '/feed');
        }

        $postId = Post::create((int) $user['id'], $body, $visibility, $replyTo);
        if ($media) {
            Post::attachMedia($postId, $media);
        }

        if (Request::wantsJson()) {
            $post = Post::find($postId, (int) $user['id']);
            json_response([
                'ok'   => true,
                'id'   => $postId,
                'html' => $this->view->partial('post', ['post' => $post, 'viewer' => $user]),
            ]);
        }
        flash('Posted.');
        redirect($replyTo ? "/p/{$replyTo}" : '/feed');
    }

    public function show(array $params): void
    {
        $viewer = current_user();
        $viewerId = $viewer ? (int) $viewer['id'] : null;
        $post = Post::find((int) $params['id'], $viewerId);
        if (!$post) {
            abort(404, 'This post is unavailable.');
        }
        if (!empty($post['author_suspended_at']) && (($viewer['role'] ?? 'user') !== 'admin')) {
            abort(404, 'This post is unavailable.');
        }
        $replies = Post::replies((int) $post['id'], $viewerId);
        $parent = $post['reply_to_id'] ? Post::find((int) $post['reply_to_id'], $viewerId) : null;

        $indexable = $post['visibility'] === 'public' && !$post['author_private'];
        $snippet = mb_substr(trim(preg_replace('/\s+/', ' ', $post['body'])), 0, 180)
            ?: "A post by @{$post['username']} on " . config('app_name');

        $this->render('post', [
            'meta' => $this->meta([
                'title'       => "{$post['display_name']} on " . config('app_name') . ": \"" . mb_substr($post['body'], 0, 60) . '"',
                'description' => $snippet,
                'canonical'   => url("/p/{$post['id']}"),
                'robots'      => $indexable ? 'index,follow' : 'noindex,nofollow',
                'og_image'    => !empty($post['media']) ? url(media_url($post['media'][0]['path'])) : url('/assets/img/og-default.png?v=2'),
                'type'        => 'article',
                'jsonld'      => $indexable ? $this->postJsonLd($post) : null,
            ]),
            'post'    => $post,
            'parent'  => $parent,
            'replies' => $replies,
            'viewer'  => $viewer,
        ], $viewer ? 'app' : 'marketing');
    }

    public function edit(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $body = (string) Request::input('body', '');
        $visibility = Request::input('visibility');
        if (looks_like_flood($body)) {
            $this->fail('That looks like spam. Try writing something more varied.', "/p/{$params['id']}");
        }
        $updated = Post::update((int) $params['id'], (int) $user['id'], $body, $visibility);

        if (Request::wantsJson()) {
            if (!$updated) {
                json_response(['ok' => false, 'error' => 'Could not update that post.'], 422);
            }
            json_response([
                'ok'   => true,
                'html' => $this->view->partial('post', ['post' => $updated, 'viewer' => $user]),
            ]);
        }
        flash($updated ? 'Post updated.' : 'Could not update that post.', $updated ? 'success' : 'error');
        redirect($updated ? "/p/{$params['id']}" : '/feed');
    }

    public function report(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $post = Post::find((int) $params['id'], (int) $user['id']);
        if ($post) {
            \App\Models\Report::file(
                (int) $user['id'], 'post', (int) $post['id'],
                (string) Request::input('reason', 'other'),
                (string) Request::input('note', '')
            );
        }
        if (Request::wantsJson()) {
            json_response(['ok' => true]);
        }
        flash('Thanks — our team will review this post.');
        redirect("/p/{$params['id']}");
    }

    public function destroy(array $params): void
    {
        $this->verifyCsrf();
        $user = require_login();
        $ok = Post::delete((int) $params['id'], (int) $user['id']);
        if (Request::wantsJson()) {
            json_response(['ok' => $ok]);
        }
        flash($ok ? 'Post deleted.' : 'Could not delete that post.');
        redirect('/feed');
    }

    public function likers(array $params): void
    {
        $viewer = current_user();
        $post = Post::find((int) $params['id'], $viewer ? (int) $viewer['id'] : null);
        if (!$post) {
            abort(404);
        }
        $this->render('likers', [
            'meta'  => $this->meta(['title' => 'Liked by — ' . config('app_name'), 'robots' => 'noindex']),
            'post'  => $post,
            'people' => Post::likers((int) $post['id']),
            'viewer' => $viewer,
        ], $viewer ? 'app' : 'marketing');
    }

    /** @return array<int,array{path:string,width:int,height:int,alt:string}> */
    private function intakeImages(): array
    {
        $files = $_FILES['images'] ?? null;
        if (!$files || !is_array($files['name'])) {
            return [];
        }
        $max = (int) config('max_images_perpost', 4);
        $alts = (array) Request::raw('image_alt', []);
        $out = [];
        $count = min(count($files['name']), $max);
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $result = Image::process([
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ], 'posts', (int) config('image_max_edge', 1600));

            if (isset($result['error'])) {
                $this->fail($result['error'], '/feed');
            }
            $result['alt'] = (string) ($alts[$i] ?? '');
            $out[] = $result;
        }
        return $out;
    }

    private function fail(string $message, string $back): never
    {
        if (Request::wantsJson()) {
            json_response(['ok' => false, 'error' => $message], 422);
        }
        flash($message);
        redirect($back);
    }

    private function postJsonLd(array $post): string
    {
        return json_encode(array_filter([
            '@context'      => 'https://schema.org',
            '@type'         => 'SocialMediaPosting',
            'datePublished' => date('c', strtotime($post['created_at'])),
            'author'        => [
                '@type' => 'Person',
                'name'  => $post['display_name'],
                'url'   => url('@' . $post['username']),
            ],
            'articleBody'   => $post['body'] ?: null,
            'url'           => url("/p/{$post['id']}"),
            'interactionStatistic' => [
                '@type'                => 'InteractionCounter',
                'interactionType'      => 'https://schema.org/LikeAction',
                'userInteractionCount' => (int) $post['like_count'],
            ],
        ]));
    }
}
