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

## Deploy to Namecheap shared hosting

1. **Create a database** in cPanel → *MySQL Databases*. Note the DB name, user, password
   (they'll be prefixed, e.g. `cpanelusr_emchat`).

2. **Upload the files.** Two options:

   **A — domain document root is changeable (preferred).**
   Upload the whole `emchat/` folder somewhere like `/home/USER/emchat/`, then in
   cPanel → *Domains* set the document root of `emchat.social` to `/home/USER/emchat/public`.

   **B — stuck with `public_html`.**
   Put the contents of `public/` directly into `public_html/`, and upload `app/`,
   `config/`, `database/`, `storage/` one level up (into `/home/USER/`). Then edit
   the first lines of `public_html/index.php` so `EMCHAT_APP_DIR` points at your `app/`
   folder (or set an `EMCHAT_APP_DIR` env var in `.htaccess`:
   `SetEnv EMCHAT_APP_DIR /home/USER/app`).

3. **Permissions.** Make `storage/` and `public/media/` writable (usually `755` is fine on
   Namecheap; use `775` if PHP runs as a different user).

4. **Run the installer.** Visit `https://emchat.social/install.php`, enter the site URL,
   DB credentials and mail choice. It imports the schema and writes `config/config.php`.

5. **Delete `public/install.php`.**

6. **Email (magic links).** Configuration can come from `config/config.php` **or** a
   `.env` file in the project root (copy `.env.example` → `.env`; `.env` is git-ignored
   and overrides `config.php`). Two options:

   **Resend (recommended, HTTPS API):**
   ```
   RESEND_API_KEY=re_xxxxxxxx
   RESEND_FROM=no-reply@emchat.social
   ```
   Verify your sending domain in the Resend dashboard first (add the DNS records they
   give you). While a domain is unverified, Resend only delivers to your own account
   email; it also refuses `@example.com` test addresses. The driver auto-selects
   `resend` whenever `RESEND_API_KEY` is set.

   **SMTP (cPanel mailbox):**
   ```
   MAIL_DRIVER=smtp
   SMTP_HOST=mail.emchat.social
   SMTP_PORT=465
   SMTP_ENCRYPTION=ssl
   SMTP_USER=hello@emchat.social
   SMTP_PASS=your_mailbox_password
   ```
   Create the mailbox in cPanel → *Email Accounts* and set SPF/DKIM so links don't
   land in spam.

   If sending ever fails, the check-your-email page shows the sign-in link on screen
   as a fallback so nobody is locked out. Set `APP_DEBUG=true` locally to always show it.

7. **SEO / Search Console.**
   - Verify `https://emchat.social` in Google Search Console (HTML-tag or DNS).
   - Submit `https://emchat.social/sitemap.xml`.
   - `robots.txt` already allows `/`, `/login`, `/explore`, `/about`, `/privacy`, `/terms`
     and public profiles/posts; it blocks the app's private areas.
   - Replace `public/assets/img/og-default.png` and the logos with real brand art
     (same filenames) when you have them.

8. **Force HTTPS.** Enable AutoSSL in cPanel. The `.htaccess` already redirects HTTP→HTTPS
   and `www`→apex (flip that rule if you prefer `www`).

### PHP settings (cPanel → *Select PHP Version*)
Enable extensions: `pdo_mysql`, `gd`, `mbstring`, `openssl`, `fileinfo`, `curl`.
For video attachments set `upload_max_filesize` ≥ `45M`, `post_max_size` ≥ `50M`,
`max_execution_time` ≥ `60`. Lower `max_video_bytes` in `config/config.php` if your plan caps uploads.

### "Real-time" on shared hosting
There are no WebSockets (shared hosting kills long-running processes), so live updates use
lightweight polling: open conversations poll every 3.5s, nav badges every 12s, and both pause
while the tab is hidden. This scales fine for a community-sized site. If you later move to a VPS
you can swap `/x/pulse` and the message poll for SSE or a websocket without touching the UI.

### Migrating an existing install
Run the SQL files in `database/migrations/` in order against your database (the base
`database/schema.sql` already includes them for fresh installs).

---

## Local development

```bash
# from the repo root, with PHP 8.1+ and a local MySQL/MariaDB
php -S localhost:8888 -t public public/index.php    # or use the built-in router
```

Create `config/config.php` from `config/config.example.php` (or run `/install.php`),
point it at your local DB, load `database/schema.sql`, set `'debug' => true` and
`'mail' => ['driver' => 'log', ...]` (sign-in links get written to `storage/mail/`
and shown on the check-email screen).

---

## Customising

- **Brand colours:** CSS variables at the top of `public/assets/css/app.css` (`--brand`, etc.).
- **Copy:** `app/views/home.php`, `about.php`, `privacy-policy.php`, `terms.php`.
- **Reserved handles:** `App\Models\User::RESERVED` in `app/src/Models/User.php`.
- **Rate limits / token lifetime / upload caps:** `config/config.php`.
