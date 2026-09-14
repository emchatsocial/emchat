<?php /** @var array $user  @var string $section */ ?>
<div class="page-head"><h1>Settings</h1></div>
<?= $view->partial('settings_nav', ['section' => $section]) ?>

<section class="panel">
  <h2 class="panel__title">Sign-in email</h2>
  <p><strong><?= e($user['email']) ?></strong> — verified <?= e(date('M j, Y', strtotime($user['email_verified_at'] ?? 'now'))) ?>.</p>
  <p class="muted">Your account has no password. Sign-in links are the only way in.</p>
</section>

<section class="panel">
  <h2 class="panel__title">Download your data</h2>
  <p>Get a machine-readable JSON copy of your account, posts, comments, likes, follows, and messages you sent.</p>
  <form method="post" action="<?= e(url('/settings/account/export')) ?>">
    <?= csrf_field() ?>
    <button class="btn btn--ghost" type="submit">Export my data</button>
  </form>
</section>

<section class="panel">
  <h2 class="panel__title">Log out</h2>
  <form method="post" action="<?= e(url('/logout')) ?>">
    <?= csrf_field() ?>
    <button class="btn btn--ghost" type="submit">Log out of EMChat</button>
  </form>
</section>

<section class="panel panel--danger">
  <h2 class="panel__title">Delete account</h2>
  <p>This permanently removes your profile, posts, photos, messages, and connections. It cannot be undone.</p>
  <form method="post" action="<?= e(url('/settings/account/delete')) ?>" onsubmit="return confirm('Permanently delete your account? This cannot be undone.')">
    <?= csrf_field() ?>
    <label class="field">
      <span class="field__label">Type <strong>@<?= e($user['username']) ?></strong> to confirm</span>
      <input type="text" name="confirm" autocomplete="off" placeholder="@<?= e($user['username']) ?>">
    </label>
    <button class="btn btn--danger" type="submit">Delete my account</button>
  </form>
</section>
