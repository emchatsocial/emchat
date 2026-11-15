<?php
declare(strict_types=1);

namespace App\Controllers;

use App\App;
use App\Controllers\HomeController;

final class SitemapController extends Controller
{
    public function index(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');

        $latestUpdate = (new HomeController())->latestUpdateDate();

        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'freq' => 'daily'],
            ['loc' => url('/about'), 'priority' => '0.8', 'freq' => 'monthly'],
            ['loc' => url('/updates'), 'lastmod' => $latestUpdate, 'priority' => '0.7', 'freq' => 'weekly'],
            ['loc' => url('/transparency'), 'priority' => '0.6', 'freq' => 'monthly'],
            ['loc' => url('/login'), 'priority' => '0.5', 'freq' => 'monthly'],
            ['loc' => url('/contact'), 'priority' => '0.5', 'freq' => 'yearly'],
            ['loc' => url('/privacy'), 'priority' => '0.4', 'freq' => 'yearly'],
            ['loc' => url('/terms'), 'priority' => '0.3', 'freq' => 'yearly'],
        ];

        $articles = BlogController::all();
        $urls[] = ['loc' => url('/blog'), 'lastmod' => $articles[0]['updated'] ?? null, 'priority' => '0.8', 'freq' => 'weekly'];
        foreach ($articles as $a) {
            $urls[] = ['loc' => url('/blog/' . $a['slug']), 'lastmod' => $a['updated'], 'priority' => '0.7', 'freq' => 'monthly'];
        }

        $people = App::db()->all(
            'SELECT username, last_seen_at, created_at FROM users
             WHERE is_private = 0 AND discoverable = 1 ORDER BY id DESC LIMIT 5000'
        );
        foreach ($people as $p) {
            $urls[] = [
                'loc'     => url('@' . $p['username']),
                'lastmod' => date('Y-m-d', strtotime($p['last_seen_at'] ?: $p['created_at'])),
                'priority' => '0.7',
                'freq'    => 'weekly',
            ];
        }
        // Public posts from public, Discoverable, non-suspended accounts.
        $posts = App::db()->all(
            "SELECT p.id, COALESCE(p.edited_at, p.created_at) AS updated FROM posts p JOIN users u ON u.id = p.user_id
             WHERE p.visibility = 'public' AND p.deleted_at IS NULL
               AND u.is_private = 0 AND u.discoverable = 1 AND u.suspended_at IS NULL
             ORDER BY p.id DESC LIMIT 20000"
        );
        foreach ($posts as $p) {
            $urls[] = [
                'loc'      => url('/p/' . $p['id']),
                'lastmod'  => date('Y-m-d', strtotime($p['updated'])),
                'priority' => '0.6',
                'freq'     => 'monthly',
            ];
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo '  <url><loc>' . e($u['loc']) . '</loc>';
            if (!empty($u['lastmod'])) {
                echo '<lastmod>' . $u['lastmod'] . '</lastmod>';
            }
            echo '<changefreq>' . $u['freq'] . '</changefreq>';
            echo '<priority>' . $u['priority'] . '</priority></url>' . "\n";
        }
        echo '</urlset>';
        exit;
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');

        $allow = [
            'Allow: /$',
            'Allow: /login',
            'Allow: /explore',
            'Allow: /about',
            'Allow: /updates',
            'Allow: /blog',
            'Allow: /transparency',
            'Allow: /contact',
            'Allow: /privacy',
            'Allow: /terms',
            'Disallow: /feed',
            'Disallow: /messages',
            'Disallow: /notifications',
            'Disallow: /settings',
            'Disallow: /welcome',
            'Disallow: /auth/',
            'Disallow: /x/',
            'Disallow: /*?*',
        ];

        // AI answer-engine / assistant crawlers get the same public pages as
        // search engines, explicitly — so EMChat can actually be read, quoted
        // and cited by chatbots, not just indexed by search.
        $aiAgents = [
            'GPTBot', 'ChatGPT-User', 'OAI-SearchBot',   // OpenAI
            'ClaudeBot', 'anthropic-ai', 'Claude-Web',    // Anthropic
            'Google-Extended',                            // Google Gemini / AI Overviews training
            'PerplexityBot', 'Perplexity-User',           // Perplexity
            'Bingbot',                                     // Bing / Copilot
            'meta-externalagent',                          // Meta AI
            'CCBot',                                       // Common Crawl (used to train many models)
            'Applebot-Extended',                           // Apple Intelligence
        ];

        $lines = ['User-agent: *', ...$allow, ''];
        foreach ($aiAgents as $agent) {
            $lines[] = "User-agent: {$agent}";
            array_push($lines, ...$allow);
            $lines[] = '';
        }
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');

        echo implode("\n", $lines);
        exit;
    }
}
