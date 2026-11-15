<?php
declare(strict_types=1);

namespace App\Controllers;

use App\App;
use App\Request;

final class HomeController extends Controller
{
    public function index(): void
    {
        if (current_user()) {
            redirect('/feed');
        }
        $this->render('home', [
            'meta' => $this->meta([
                'title'       => 'EMChat Media: A Private Social Network Without Ads',
                'description' => 'Built independently in North Macedonia, EMChat Media skips ad trackers and '
                    . 'behavioural profiling. Your feed only shows the people you actually follow.',
                'type'        => 'website',
                'jsonld'      => $this->organizationJsonLd(),
            ]),
        ], 'marketing');
    }

    public function about(): void
    {
        $faqs = $this->faqs();
        $this->render('about', [
            'meta' => $this->meta([
                'title'       => 'About EMChat Media: Built for Privacy, Not Profit',
                'description' => 'EMChat Media is a social network built for privacy, founded in 2026 in North '
                    . 'Macedonia by Egzon Mehmedi. Here is why it exists and how it works.',
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
                'EMChat Media is a social network built for privacy, founded in 2026 in North Macedonia. It '
                . 'offers a feed of posts from the people you follow, private messaging and group chats, and '
                . 'profile pages, with no ad trackers or behavioural profiling.'],
            ['Is EMChat Media free to use?',
                'Yes, EMChat Media is free to use. There is no advertising business behind it, so no paid tier '
                . 'is required for the core features.'],
            ['Does EMChat Media sell or share my data?',
                'No. EMChat Media has no advertising network or data broker relationships. Uploaded photos are '
                . 'rebuilt on our server to strip EXIF and GPS metadata, and you can export or permanently delete '
                . 'your data at any time.'],
            ['How is EMChat Media different from Instagram, Facebook, or X?',
                'Unlike platforms funded by advertising, EMChat Media has no ad trackers and no behavioural profiling. Your '
                . 'feed is only posts from the people you follow, newest first, with nothing else mixed in and '
                . 'nothing ranked to maximise how long you scroll. See our transparency page for the full details.'],
            ['What does setting a post to "Public" actually do?',
                'It makes the post visible to anyone who visits your profile, not just your followers. It is '
                . 'never pushed into other people\'s home feeds and never listed on Explore, so the only way '
                . 'someone sees it is by visiting your profile or opening a direct link to it. Choose "Followers" '
                . 'instead if you\'d rather only the people who follow you see it at all.'],
            ['Who founded EMChat Media?',
                'EMChat Media was founded by Egzon Mehmedi and is built and run independently from North Macedonia.'],
            ['Do I need a password to sign in to EMChat Media?',
                'No. EMChat Media does not use passwords. You get a link by email that works once, so there is '
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
                'description' => 'How EMChat Media collects, uses and protects personal information, and the choices available to you.',
            ]),
        ], 'marketing');
    }

    public function terms(): void
    {
        $this->render('terms', [
            'meta' => $this->meta([
                'title'       => 'Terms of Service · ' . config('app_name'),
                'description' => 'The terms that govern your use of EMChat Media, including your account, your content and acceptable use.',
            ]),
        ], 'marketing');
    }

    public function transparency(): void
    {
        $this->render('transparency', [
            'meta' => $this->meta([
                'title'       => 'Transparency: How EMChat Media Handles Your Data',
                'description' => 'How EMChat Media handles your data, encrypts your messages end to end, how the feed works, and where to review the source code.',
                'canonical'   => url('/transparency'),
            ]),
        ], 'marketing');
    }

    public function contact(): void
    {
        $viewer = current_user();
        $this->render('contact', [
            'meta' => $this->meta([
                'title'       => 'Contact EMChat Media',
                'description' => 'Questions, feedback, or press inquiries? Send EMChat Media a message, or email '
                    . 'egzon@emchat.social directly.',
                'canonical'   => url('/contact'),
            ]),
        ], $viewer ? 'app' : 'marketing');
    }

    public function sendContact(): void
    {
        $this->verifyCsrf();
        $email = mb_strtolower(trim((string) Request::input('email', '')));
        $message = trim((string) Request::input('message', ''));

        if (Request::input('website_url', '') !== '') {
            redirect('/contact');
        }
        $_SESSION['_old']['email'] = $email;
        $_SESSION['_old']['message'] = $message;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Enter a valid email address.');
            redirect('/contact');
        }
        if ($message === '' || mb_strlen($message) > 4000) {
            flash('Write a message (up to 4000 characters).');
            redirect('/contact');
        }
        unset($_SESSION['_old']);
        if (!App::limiter()->attempt('contact:' . $email, 5, 600)) {
            flash('You have sent too many messages. Please wait a while before trying again.', 'error');
            redirect('/contact');
        }

        $html = '<div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;max-width:560px;margin:0 auto;color:#1a1a2e">'
            . '<h2 style="margin:0 0 8px">New contact message</h2>'
            . '<p style="color:#555">From: ' . e($email) . '</p>'
            . '<p style="white-space:pre-wrap">' . e($message) . '</p></div>';
        $text = "From: {$email}\n\n{$message}";
        App::mailer()->send('egzon@emchat.social', 'New EMChat contact message', $html, $text);

        flash('Thanks, your message has been sent.', 'success');
        redirect('/contact');
    }

    public function updates(): void
    {
        $entries = array_map(function (array $e) {
            $e['jsonld'] = $this->blogPostingJsonLd($e);
            return $e;
        }, $this->updateEntries());
        $this->render('updates', [
            'meta' => $this->meta([
                'title'       => 'EMChat Media Updates: News and New Features',
                'description' => 'News, feature releases, and milestones from EMChat Media, newest first.',
                'canonical'   => url('/updates'),
                'jsonld'      => $this->blogJsonLd(),
            ]),
            'entries' => $entries,
        ], 'marketing');
    }

    /** Date of the most recent entry, for the sitemap's <lastmod>. */
    public function latestUpdateDate(): string
    {
        return $this->updateEntries()[0]['date'];
    }

    /** @return array<int,array{date:string,headline:string,body:string}> Newest first. */
    private function updateEntries(): array
    {
        return [
            ['date' => '2026-11-15', 'headline' => 'Your messages are now end-to-end encrypted',
                'body' => 'Direct messages, group chats, and the photos, videos and files you send in them are now '
                    . 'encrypted on your device before they leave it. Only the people in the conversation can read '
                    . 'them, and we can\'t. Encryption is on by default, with nothing to set up: a conversation is '
                    . 'encrypted once everyone in it has opened EMChat since today, and the chat shows when that\'s '
                    . 'the case. We don\'t scan your messages. Photos in messages also have their location and other '
                    . 'hidden data removed on your device before they\'re encrypted.'],
            ['date' => '2026-11-15', 'headline' => 'Backup PIN and a new Encryption page in Settings',
                'body' => 'Your encryption key lives in your browser. Set a backup PIN in Settings > Encryption so you '
                    . 'can restore your encrypted messages after clearing your browser or on a new one. From the same '
                    . 'page you can change or remove your PIN, create a new key, or remove your key from a shared computer. '
                    . 'We never see your PIN, so keep it somewhere safe.'],
            ['date' => '2026-11-15', 'headline' => '"Seen" now updates live',
                'body' => 'In one-to-one chats, "Seen" now appears under your message within a few seconds of the other '
                    . 'person reading it, without reloading the page.'],
            ['date' => '2026-11-13', 'headline' => 'Public posts can now appear in search engines',
                'body' => 'If your account is public and Discoverable is on, your Public posts can now be indexed by '
                    . 'search engines like Google. Followers-only and Only-me posts, and everything from private '
                    . 'accounts, stay out of search. Turn off Discoverable in Settings to opt out at any time.'],
            ['date' => '2026-11-13', 'headline' => 'Introducing the EMChat Media blog',
                'body' => 'Our new blog has practical guides on privacy and social media, from removing location data '
                    . 'from your photos to how chronological and algorithmic feeds differ. Find it at emchat.social/blog.'],
            ['date' => '2026-11-13', 'headline' => 'Stronger blocking',
                'body' => 'When you block someone, they can no longer message you, including in an existing chat, '
                    . 'trigger notifications for you, or find you in search and suggestions. If someone has blocked '
                    . 'you, their profile now says so instead of showing an error. You can unblock people from the chat '
                    . 'or from Settings > Privacy & safety.'],
            ['date' => '2026-11-13', 'headline' => 'Public posts on private accounts',
                'body' => 'If your account is private, posts you mark Public are now shown on your profile to everyone, '
                    . 'as intended. Followers-only posts, and your followers and following lists, stay limited to your '
                    . 'approved followers.'],
            ['date' => '2026-11-13', 'headline' => 'Smaller improvements',
                'body' => '"Who to follow" is now "Discover people", and suspended accounts no longer appear in it. '
                    . 'Deleting a chat now reliably removes it from your inbox until a new message arrives.'],
            ['date' => '2026-09-20', 'headline' => 'Public posts now stay on your profile',
                'body' => 'A public post is now only shown to people who visit your profile or open a direct link '
                    . 'to it. It is no longer pushed into strangers\' feeds or listed on Explore, and individual '
                    . 'posts are kept out of search engines.'],
            ['date' => '2026-09-20', 'headline' => 'A contact page',
                'body' => 'You can now reach us from the Contact link in the menu, without leaving EMChat Media or '
                    . 'opening your own email app.'],
            ['date' => '2026-09-20', 'headline' => 'A quick tour of what EMChat actually does',
                'body' => 'Private messaging with group chats and photo, video, and file attachments. Mentions '
                    . 'and hashtags. Notifications for likes, replies, and follows. Private accounts, per-post '
                    . 'visibility, and one-click data export or deletion, all without ad trackers or an algorithm '
                    . 'deciding what you see.'],
            ['date' => '2026-09-20', 'headline' => 'Structured data and this updates page',
                'body' => 'We added richer structured data so search engines and AI assistants can understand '
                    . 'EMChat better, plus this page to keep a public record of what changes and when.'],
            ['date' => '2026-09-19', 'headline' => 'Faster page loads across the site',
                'body' => 'We enabled faster connection resumption for returning visitors and let browsers start '
                    . 'fetching key resources earlier, plus removed a third-party script that was slowing down '
                    . 'every page without adding anything for you.'],
            ['date' => '2026-09-19', 'headline' => 'More reliable account emails',
                'body' => 'Sign-in links and account emails were sometimes landing in spam because of a mail '
                    . 'server misconfiguration. We fixed DKIM signing and corrected our mail routing so delivery '
                    . 'is reliable going forward.'],
            ['date' => '2026-09-18', 'headline' => 'We stopped collecting IP addresses',
                'body' => 'EMChat no longer stores your IP address anywhere, not even for spam prevention or '
                    . 'sign-in rate limiting. Those protections now work without ever touching it, and we purged '
                    . 'any IP data collected before this change.'],
            ['date' => '2026-09-17', 'headline' => 'Faster on mobile',
                'body' => 'We found our app icons were being fetched at full size on every page load, which was '
                    . 'hurting mobile performance. They are now properly compressed with no visible quality loss.'],
            ['date' => '2026-09-11', 'headline' => 'EMChat Media goes live',
                'body' => 'We launched EMChat Media, an independent social network built from North Macedonia '
                    . 'with no ad trackers and no algorithm deciding what you see.'],
        ];
    }

    /** Same @id as the homepage Organization node, so crawlers resolve every page to one entity. */
    private function publisherRef(): array
    {
        return [
            '@type' => 'Organization',
            '@id'   => url('/#organization'),
            'name'  => config('app_name'),
            'url'   => url('/'),
            'logo'  => url('/assets/img/logo-512.png?v=3'),
        ];
    }

    private function blogJsonLd(): string
    {
        return json_encode([
            '@context'    => 'https://schema.org',
            '@type'       => 'Blog',
            'name'        => 'EMChat Media Updates',
            'url'         => url('/updates'),
            'description' => 'News, feature releases, and milestones from EMChat Media.',
            'inLanguage'  => 'en',
            'publisher'   => $this->publisherRef(),
        ], JSON_UNESCAPED_SLASHES);
    }

    /** @param array{date:string,headline:string,body:string} $entry */
    private function blogPostingJsonLd(array $entry): string
    {
        return json_encode([
            '@context'         => 'https://schema.org',
            '@type'            => 'BlogPosting',
            'headline'         => $entry['headline'],
            'datePublished'    => $entry['date'],
            'dateModified'     => $entry['date'],
            'description'      => $entry['body'],
            'url'              => url('/updates'),
            'mainEntityOfPage' => url('/updates'),
            'author'           => $this->publisherRef(),
            'publisher'        => $this->publisherRef(),
        ], JSON_UNESCAPED_SLASHES);
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
                    'alternateName'    => ['EMChat', 'emchat.social'],
                    'email'            => 'egzon@emchat.social',
                    'url'              => url('/'),
                    'logo'             => url('/assets/img/logo-512.png?v=3'),
                    'sameAs'           => ['https://github.com/emchatsocial'],
                    'description'      => 'EMChat Media is a social network built for privacy, made independently in North Macedonia.',
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
                    'alternateName'   => ['EMChat', 'emchat.social'],
                    'url'             => url('/'),
                    'publisher'       => ['@id' => url('/#organization')],
                    'inLanguage'      => 'en',
                    'dateModified'    => date('Y-m-d'),
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
