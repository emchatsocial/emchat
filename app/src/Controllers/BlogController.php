<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * Blog: articles live as plain PHP files in app/content/blog/<slug>.php,
 * each returning ['title', 'description', 'date', 'updated'?, 'body' (HTML)].
 * To publish a new article, drop a new file in that folder. No database needed.
 */
final class BlogController extends Controller
{
    public function index(): void
    {
        $articles = self::all();
        $this->render('blog/index', [
            'meta' => $this->meta([
                'title'       => 'EMChat Media Blog: Privacy, Social Media and Staying in Control',
                'description' => 'Practical guides on online privacy, photo safety and using social media on your own terms, from the team behind EMChat Media.',
                'canonical'   => url('/blog'),
                'jsonld'      => $this->blogJsonLd($articles),
            ]),
            'articles' => $articles,
        ], 'marketing');
    }

    public function show(array $params): void
    {
        $article = self::find((string) ($params['slug'] ?? ''));
        if (!$article) {
            abort(404, 'That article doesn\'t exist.');
        }
        $others = array_values(array_filter(self::all(), fn ($a) => $a['slug'] !== $article['slug']));
        $this->render('blog/show', [
            'meta' => $this->meta([
                'title'       => $article['title'] . ' · EMChat Media Blog',
                'description' => $article['description'],
                'canonical'   => url('/blog/' . $article['slug']),
                'type'        => 'article',
                'jsonld'      => $this->postingJsonLd($article),
            ]),
            'article' => $article,
            'others'  => array_slice($others, 0, 3),
        ], 'marketing');
    }

    /** @return array<int,array> newest first */
    public static function all(): array
    {
        $out = [];
        foreach (glob(dirname(__DIR__, 2) . '/content/blog/*.php') ?: [] as $file) {
            $slug = basename($file, '.php');
            if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
                continue;
            }
            $a = require $file;
            if (!is_array($a) || empty($a['title']) || empty($a['date']) || empty($a['body'])) {
                continue;
            }
            $a['slug'] = $slug;
            $a['updated'] = $a['updated'] ?? $a['date'];
            $out[] = $a;
        }
        usort($out, fn ($x, $y) => strcmp($y['date'], $x['date']));
        return $out;
    }

    public static function find(string $slug): ?array
    {
        foreach (self::all() as $a) {
            if ($a['slug'] === $slug) {
                return $a;
            }
        }
        return null;
    }

    private function publisher(): array
    {
        return [
            '@type' => 'Organization',
            '@id'   => url('/#organization'),
            'name'  => config('app_name'),
            'url'   => url('/'),
            'logo'  => url('/assets/img/logo-512.png?v=3'),
        ];
    }

    private function blogJsonLd(array $articles): string
    {
        return json_encode([
            '@context'    => 'https://schema.org',
            '@type'       => 'Blog',
            'name'        => 'EMChat Media Blog',
            'url'         => url('/blog'),
            'inLanguage'  => 'en',
            'publisher'   => $this->publisher(),
            'blogPost'    => array_map(fn ($a) => [
                '@type'         => 'BlogPosting',
                'headline'      => $a['title'],
                'url'           => url('/blog/' . $a['slug']),
                'datePublished' => $a['date'],
            ], $articles),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function postingJsonLd(array $a): string
    {
        return json_encode([
            '@context'         => 'https://schema.org',
            '@type'            => 'BlogPosting',
            'headline'         => $a['title'],
            'description'      => $a['description'],
            'datePublished'    => $a['date'],
            'dateModified'     => $a['updated'],
            'url'              => url('/blog/' . $a['slug']),
            'mainEntityOfPage' => url('/blog/' . $a['slug']),
            'inLanguage'       => 'en',
            'image'            => url('/assets/img/og-default.png?v=3'),
            'author'           => $this->publisher(),
            'publisher'        => $this->publisher(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
