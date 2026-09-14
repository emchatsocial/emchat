# EMChat Media

A privacy-first social network: passwordless auth, rich profile cards, photo posts,
follows / likes / comments, direct messages, notifications, private accounts, block/mute,
data export & account deletion. Server-rendered PHP so the homepage, login and public
profiles index cleanly in Google Search Console.

- **Stack:** PHP 8.1+ and MySQL/MariaDB. No Composer, no build step, no CDN, no trackers.
- **Domain:** `emchat.social`
- **License:** Source-available, not open source. The code is public so anyone can verify what
  it does and doesn't do (see [`LICENSE`](LICENSE)) — you're welcome to read it, learn from it,
  run it for personal/noncommercial purposes, and open issues or pull requests. Running it, or a
  derivative of it, as a commercial or competing product is not permitted without permission.

---

## What's in the box

| Area | Feature |
|---|---|
| Auth | Magic-link sign in (no passwords), one-time tokens, rate limiting, honeypot |
| Onboarding | 3-step wizard after first sign-in: claim handle (live availability check via `/x/username-available`), display name, optional profile photo + private-account choice. Works without JS as a single form. |
| Profiles | Rich profile cards (profile photo, bio with links/mentions, stats, follow/message), shareable card page at `/@user/card`, followers/following lists |
| Posts | Text + up to 4 images, visibility (public / followers / private), replies, delete |
| Media privacy | Every upload re-encoded with GD → EXIF/GPS stripped automatically |
| Social | Follow / unfollow, follow requests for private accounts, likes, threaded replies, mentions, hashtags |
| Messaging | 1:1 conversations, a people picker to start new chats, photo/video/audio/PDF/doc attachments (images re-encoded to strip metadata), reply to a message, edit within 90 minutes, delete for everyone (tombstone) or delete for me (hidden from your view), live delivery + live edit/delete sync via 3.5s polling, unread counts |
| Notifications | like / follow / follow-request / reply / mention / message, grouped; nav badges refresh live (12s pulse) |
| Posts extras | Inline edit (with "edited" marker), per-post visibility change, copy link, and for others' posts: mute / block / report with a reason picker |
| Feedback | Toast notifications for every action ("Posted", "Post updated", "Uploading… 60%", "Saved", "Blocked @user", …); privacy toggles autosave on change |
| Privacy | Private accounts, per-post visibility, block, mute, discoverability toggle, JSON data export, hard account delete |
| SEO | Server-rendered HTML, per-page `<title>`/meta/canonical, Open Graph + Twitter cards, JSON-LD (Organization, WebSite, ProfilePage, SocialMediaPosting), `/sitemap.xml`, `/robots.txt` |
| Security | CSRF on every POST, prepared statements everywhere, strict CSP, HSTS, `X-Frame-Options`, secure cookies |

---

## Directory layout

```
emchat/
├── public/            ← web root (point the domain here)
│   ├── index.php       front controller
│   ├── .htaccess       rewrites + security headers
│   ├── install.php     one-time browser installer (delete after use)
│   ├── assets/         css / js / images  (no external deps)
│   └── media/          uploaded images (writable)
├── app/               application code (PHP, views) — not web-accessible
├── config/            config.php lives here (created by installer)
├── database/schema.sql
└── storage/           logs, sessions, cache, dev mail  (writable)
```

`app/`, `config/`, `storage/`, `database/` sit **outside** the web root. Each also ships a
deny-all `.htaccess` in case your host forces everything into `public_html`.

---

## Running your own copy

This repository is published for transparency and review, not as a turnkey product (see
**License** above). It's a standard PHP 8.1+ / MySQL app with no build step: point a webserver
at `public/`, supply your own database and mail configuration, and load `database/schema.sql`.
Deployment specifics (hosting provider, environment variable names, mail setup) are
intentionally left out of this README; open an issue if you're a contributor and need them.

### "Real-time" without WebSockets
There are no long-running WebSocket connections. Live updates use lightweight polling instead:
open conversations poll every 3.5s, nav badges every 12s, and both pause while the tab is hidden.

---

## Customising

- **Brand colours:** CSS variables at the top of `public/assets/css/app.css` (`--brand`, etc.).
- **Copy:** `app/views/home.php`, `about.php`, `privacy-policy.php`, `terms.php`.
- **Reserved handles:** `App\Models\User::RESERVED` in `app/src/Models/User.php`.
- **Rate limits / token lifetime / upload caps:** `config/config.php`.
