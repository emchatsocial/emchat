<?php
declare(strict_types=1);

namespace App\Controllers;

use App\App;
use App\Request;
use App\Models\User;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (current_user()) {
            redirect('/feed');
        }
        $this->render('auth/login', [
            'meta' => $this->meta([
                'title'       => 'Log in or sign up · ' . config('app_name'),
                'description' => 'Sign in to EMChat Media with a one-time link sent to your email. No password to remember, nothing to leak.',
                'canonical'   => url('/login'),
            ]),
        ], 'auth');
    }

    public function sendLink(): void
    {
        $this->verifyCsrf();
        $email = mb_strtolower((string) Request::input('email', ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['_old']['email'] = $email;
            flash('Enter a valid email address.');
            redirect('/login');
        }

        // Honeypot: bots fill hidden fields.
        if (Request::input('website_url', '') !== '') {
            redirect('/login/check');
        }

        $this->dispatchLink($email);
    }

    /** Re-send the sign-in link to the address already in progress. */
    public function resendLink(): void
    {
        $this->verifyCsrf();
        $email = mb_strtolower((string) ($_SESSION['_login_email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirect('/login');
        }
        $this->dispatchLink($email, true);
    }

    /** Mint a one-time link, email it, and route to the "check your email" page. */
    private function dispatchLink(string $email, bool $isResend = false): never
    {
        $ipBucket = 'login_ip:' . Request::ip();
        $emailBucket = 'login_email:' . $email;
        $rate = config('login_rate');
        $limiter = App::limiter();
        if (!$limiter->attempt($ipBucket, $rate['max'] * 3, $rate['per_seconds'])
            || !$limiter->attempt($emailBucket, $rate['max'], $rate['per_seconds'])) {
            flash('You have requested too many links. Please wait a while before trying again.', 'error');
            redirect($isResend ? '/login/check' : '/login');
        }

        $raw = App::auth()->createLoginToken($email, Request::ipBinary());
        $link = url('/auth/callback?token=' . $raw);
        $sent = $this->sendMagicLink($email, $link);

        $_SESSION['_login_email'] = $email;
        // NEVER surface the sign-in link on screen in production — doing so would
        // let anyone log in as any email address. Only in local debug mode.
        unset($_SESSION['_debug_link']);
        if (config('debug')) {
            $_SESSION['_debug_link'] = $link;
        } elseif (!$sent) {
            flash('We couldn\'t send the email just now. Please try again in a few minutes.', 'error');
            redirect($isResend ? '/login/check' : '/login');
        }

        if ($isResend && ($sent || config('debug'))) {
            flash('We sent another sign-in link to ' . $email . '.', 'success');
        }
        redirect('/login/check');
    }

    public function checkEmail(): void
    {
        $email = $_SESSION['_login_email'] ?? null;
        if (!$email) {
            redirect('/login');
        }
        $this->render('auth/check', [
            'meta'       => $this->meta(['title' => 'Check your email · ' . config('app_name'), 'robots' => 'noindex,follow']),
            'email'      => $email,
            'debug_link' => $_SESSION['_debug_link'] ?? null,
        ], 'auth');
    }

    public function callback(): void
    {
        $raw = (string) Request::input('token', '');
        $email = App::auth()->consumeLoginToken($raw);
        if (!$email) {
            if (current_user()) {
                // Already signed in (e.g. re-clicking an old/used link) — nothing
                // actually went wrong for them, so just send them on instead of
                // flashing an "expired" warning that showLogin() would otherwise
                // carry straight through to their feed on the redirect-when-
                // already-authenticated bounce below.
                redirect('/feed');
            }
            flash('That link is invalid or has expired. Request a new one.');
            redirect('/login');
        }

        $user = User::findByEmail($email);
        if ($user) {
            App::auth()->login((int) $user['id']);
            unset($_SESSION['_login_email'], $_SESSION['_debug_link']);
            $intended = safe_path($_SESSION['_intended'] ?? '/feed');
            unset($_SESSION['_intended']);
            redirect($intended);
        }

        // New account: stash verified email, ask for a username.
        $_SESSION['_pending_email'] = $email;
        redirect('/welcome');
    }

    public function showOnboard(): void
    {
        $email = $_SESSION['_pending_email'] ?? null;
        if (!$email) {
            redirect('/login');
        }
        $local = strstr($email . '@', '@', true);
        $suggestion = substr(strtolower(preg_replace('/[^a-z0-9_]/i', '', $local) ?: 'member'), 0, 24);
        $nameGuess = ucwords(trim(preg_replace('/[._\-]+/', ' ', $local)));
        $this->render('auth/welcome', [
            'meta'        => $this->meta(['title' => 'Set up your profile · ' . config('app_name'), 'robots' => 'noindex']),
            'email'       => $email,
            'suggestion'  => $suggestion,
            'name_guess'  => mb_substr($nameGuess, 0, 40),
        ], 'auth');
    }

    /** Live handle availability check used by the onboarding wizard. */
    public function usernameAvailable(): void
    {
        if (!\App\App::limiter()->attempt('uname:' . Request::ip(), 150, 60)) {
            json_response(['ok' => false, 'error' => 'Slow down a moment.'], 429);
        }
        $u = (string) Request::input('u', '');
        if ($u === '') {
            json_response(['ok' => true, 'available' => false, 'reason' => '']);
        }
        if (mb_strlen($u) < 3) {
            json_response(['ok' => true, 'available' => false, 'reason' => 'At least 3 characters']);
        }
        if (mb_strlen($u) > 30) {
            json_response(['ok' => true, 'available' => false, 'reason' => 'Too long (max 30)']);
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $u)) {
            json_response(['ok' => true, 'available' => false, 'reason' => 'Letters, numbers and _ only']);
        }
        if (!User::validUsername($u)) {
            json_response(['ok' => true, 'available' => false, 'reason' => 'That handle is reserved']);
        }
        $free = User::usernameAvailable($u);
        json_response(['ok' => true, 'available' => $free, 'reason' => $free ? 'Available' : 'Already taken']);
    }

    public function completeOnboard(): void
    {
        $this->verifyCsrf();
        $email = $_SESSION['_pending_email'] ?? null;
        if (!$email) {
            $this->onboardFail('Your session expired. Request a new sign-in link.', '/login');
        }
        $username = (string) Request::input('username', '');
        $displayName = trim((string) Request::input('display_name', '')) ?: $username;

        if (!User::validUsername($username)) {
            $this->onboardFail('Handles are 3–30 characters: letters, numbers, and underscores only.');
        }
        if (!User::usernameAvailable($username)) {
            $this->onboardFail('That handle was just taken. Try another.');
        }

        $avatarPath = null;
        $file = $_FILES['avatar'] ?? null;
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $edge = (int) config('avatar_edge', 400);
            $res = \App\Image::process($file, 'avatars', $edge, $edge);
            if (isset($res['error'])) {
                $this->onboardFail($res['error']);
            }
            $avatarPath = $res['path'];
        }

        $id = App::db()->insert('users', [
            'username'          => $username,
            'display_name'      => mb_substr($displayName, 0, 60),
            'email'             => $email,
            'avatar_path'       => $avatarPath,
            'is_private'        => Request::boolean('is_private') ? 1 : 0,
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);
        unset($_SESSION['_pending_email']);
        App::auth()->login($id);

        if (Request::wantsJson()) {
            json_response(['ok' => true, 'redirect' => url('/feed')]);
        }
        flash('Welcome to EMChat Media. This is your space now.');
        redirect('/feed');
    }

    private function onboardFail(string $message, string $to = '/welcome'): never
    {
        if (Request::wantsJson()) {
            json_response(['ok' => false, 'error' => $message], 422);
        }
        flash($message, 'error');
        redirect($to);
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        App::auth()->logout();
        redirect('/');
    }

    private function sendMagicLink(string $email, string $link): bool
    {
        $app = config('app_name');
        $ttl = (int) round(((int) config('login_token_ttl', 900)) / 60);
        $html = '<div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;max-width:480px;margin:0 auto;color:#1a1a2e">'
            . '<h2 style="margin:0 0 8px">Sign in to ' . e($app) . '</h2>'
            . '<p style="color:#555">Tap the button below to finish signing in. This link works once and expires in ' . $ttl . ' minutes.</p>'
            . '<p style="margin:24px 0"><a href="' . e($link) . '" style="background:#5b5bd6;color:#fff;padding:12px 22px;border-radius:10px;text-decoration:none;display:inline-block">Sign in</a></p>'
            . '<p style="color:#888;font-size:13px">Or paste this URL into your browser:<br><span style="word-break:break-all">' . e($link) . '</span></p>'
            . '<p style="color:#888;font-size:13px">If you didn\'t request this, you can safely ignore it.</p></div>';
        $text = "Sign in to {$app}\n\n{$link}\n\nThis link works once and expires in {$ttl} minutes.";
        return App::mailer()->send($email, "Your {$app} sign-in link", $html, $text);
    }
}
