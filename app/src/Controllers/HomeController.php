<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;

final class HomeController extends Controller
{
    public function index(): void
    {
        if (current_user()) {
            redirect('/feed');
        }
        $preview = Post::explore(null, null, 6);
        $this->render('home', [
            'meta' => $this->meta([
                'title'       => 'EMChat: A Social Network That Keeps Your World Yours',
                'description' => 'EMChat is a privacy-first social network from North Macedonia: no ad trackers, '
                    . 'no behavioural profiling, just posts from people you follow, newest first.',
                'type'        => 'website',
                'jsonld'      => $this->organizationJsonLd(),
            ]),
            'preview' => $preview,
        ], 'marketing');
    }

    public function about(): void
    {
        $faqs = $this->faqs();
        $this->render('about', [
            'meta' => $this->meta([
                'title'       => 'About EMChat: A Social Network Built for Privacy, Not Profit',
                'description' => 'Why EMChat exists: a privacy-first social network with no trackers or '
                    . 'behavioural profiling, founded in 2026 in North Macedonia by Egzon Mehmedi.',
                'jsonld'      => $this->faqJsonLd($faqs),
            ]),
            'faqs' => $faqs,
        ], 'marketing');
    }

    /** @return array<int,array{0:string,1:string}> Single source of truth for both the visible FAQ and its schema. */
    private function faqs(): array
    {
        return [
            ['What is EMChat Media?',
                'EMChat Media is a privacy-first social network founded in 2026 in North Macedonia. It offers a '
                . 'feed built mainly from the people you follow, private messaging and group chats, and profile '
                . 'pages, with no ad trackers or behavioural profiling.'],
            ['Is EMChat Media free to use?',
                'Yes, EMChat Media is free to use. There is no advertising business behind it, so no paid tier '
                . 'is required for the core features.'],
            ['Does EMChat Media sell or share my data?',
                'No. EMChat Media has no advertising network or data broker relationships. Uploaded photos are '
                . 're-encoded to strip EXIF and GPS metadata, and you can export or permanently delete your data '
                . 'at any time.'],
            ['How is EMChat Media different from Instagram, Facebook, or X?',
                'Unlike ad-funded platforms, EMChat Media has no ad trackers and no behavioural profiling. Your '
                . 'feed is built from the people you follow, plus a small number of clearly labelled "suggested" '
                . 'public posts so new accounts are never staring at an empty screen. Nothing is ranked to '
                . 'maximise how long you scroll. See our transparency page for the full details.'],
            ['Who founded EMChat Media?',
                'EMChat Media was founded by Egzon Mehmedi and is built and run independently from North Macedonia.'],
            ['Do I need a password to sign in to EMChat Media?',
                'No. EMChat Media uses passwordless sign-in — you receive a one-time link by email, so there is '
                . 'no password to create, remember, or have breached.'],
            ['Can I message people privately on EMChat Media?',
                'Yes. EMChat Media supports direct messages and group chats with photos, video, and file '
                . 'attachments, plus message requests so people you do not follow cannot land straight in your '
                . 'inbox without your approval.'],
            ['How do I contact EMChat Media?',
                'Email egzon@emchat.social with questions, feedback, or press inquiries.'],
        ];
    }

    /** @param array<int,array{0:string,1:string}> $faqs */
    private function faqJsonLd(array $faqs): string
    {
        return json_encode([
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(static fn ($f) => [
                '@type'          => 'Question',
                'name'           => $f[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
            ], $faqs),
        ], JSON_UNESCAPED_SLASHES);
    }

    public function privacyPolicy(): void
    {
        $this->render('privacy-policy', [
            'meta' => $this->meta([
                'title'       => 'Privacy Policy · ' . config('app_name'),
                'description' => 'Exactly what data EMChat Media collects, why, and the controls you have over it.',
            ]),
        ], 'marketing');
    }

    public function terms(): void
    {
        $this->render('terms', [
            'meta' => $this->meta([
                'title'       => 'Terms of Service · ' . config('app_name'),
                'description' => 'The terms that govern your use of EMChat Media: your account, your content, and what we do and do not do with either.',
            ]),
        ], 'marketing');
    }

    public function transparency(): void
    {
        $this->render('transparency', [
            'meta' => $this->meta([
                'title'       => 'Transparency · ' . config('app_name'),
                'description' => 'What EMChat Media actually does with your data, how the feed works, and where to read the source code yourself instead of taking our word for it.',
                'canonical'   => url('/transparency'),
            ]),
        ], 'marketing');
    }

    private function organizationJsonLd(): string
    {
        return json_encode([
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'            => 'Organization',
                    '@id'              => url('/#organization'),
                    'name'             => config('app_name'),
                    'alternateName'    => 'EMChat',
                    'email'            => 'egzon@emchat.social',
                    'url'              => url('/'),
                    'logo'             => url('/assets/img/logo-512.png?v=3'),
                    'description'      => 'EMChat Media is a privacy-first social network built independently in North Macedonia.',
                    'foundingDate'     => '2026',
                    'foundingLocation' => [
                        '@type'         => 'Place',
                        'address'       => [
                            '@type'          => 'PostalAddress',
                            'addressCountry' => 'MK',
                        ],
                    ],
                    'address' => [
                        '@type'          => 'PostalAddress',
                        'addressCountry' => 'MK',
                    ],
                    'founder' => ['@id' => url('/#founder')],
                ],
                [
                    '@type' => 'Person',
                    '@id'   => url('/#founder'),
                    'name'  => 'Egzon Mehmedi',
                    'email' => 'egzon@emchat.social',
                    'jobTitle' => 'Founder',
                    'worksFor' => ['@id' => url('/#organization')],
                ],
                [
                    '@type'           => 'WebSite',
                    '@id'             => url('/#website'),
                    'name'            => config('app_name'),
                    'alternateName'   => 'EMChat',
                    'url'             => url('/'),
                    'publisher'       => ['@id' => url('/#organization')],
                    'inLanguage'      => 'en',
                    'potentialAction' => [
                        '@type'       => 'SearchAction',
                        'target'      => url('/explore?q={search_term_string}'),
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES);
    }
}
