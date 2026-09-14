<?php /** @var array $user  @var array $blocked  @var array $muted  @var string $section */ ?>
<div class="page-head"><h1>Settings</h1></div>
<?= $view->partial('settings_nav', ['section' => $section]) ?>

<form class="form form--settings" method="post" action="<?= e(url('/settings/privacy')) ?>" data-ajax="settings" data-autosave>
  <?= csrf_field() ?>
  <label class="switch">
    <input type="checkbox" name="is_private" value="1" <?= $user['is_private'] ? 'checked' : '' ?>>
    <span class="switch__track"></span>
    <span class="switch__label"><strong>Private account</strong><small>Only approved followers can see your posts, followers and following.</small></span>
  </label>
  <label class="switch">
    <input type="checkbox" name="discoverable" value="1" <?= $user['discoverable'] ? 'checked' : '' ?>>
    <span class="switch__track"></span>
    <span class="switch__label"><strong>Discoverable</strong><small>Allow your profile to appear in search and the public sitemap indexed by search engines.</small></span>
  </label>
  <button class="btn btn--primary" type="submit" data-autosave-hide>Save</button>
</form>

<section class="panel">
  <h2 class="panel__title">Blocked accounts</h2>
  <?php if (!$blocked): ?><p class="muted">You haven't blocked anyone.</p><?php endif; ?>
  <?php foreach ($blocked as $b): ?>
    <div class="rowitem">
      <span>@<?= e($b['username']) ?></span>
      <form method="post" action="<?= e(url('/x/unblock/' . $b['username'])) ?>"><?= csrf_field() ?><button class="btn btn--ghost btn--sm">Unblock</button></form>
    </div>
  <?php endforeach; ?>
</section>

<section class="panel">
  <h2 class="panel__title">Muted accounts</h2>
  <?php if (!$muted): ?><p class="muted">Nobody muted.</p><?php endif; ?>
  <?php foreach ($muted as $m): ?>
    <div class="rowitem">
      <span>@<?= e($m['username']) ?></span>
      <form method="post" action="<?= e(url('/x/unmute/' . $m['username'])) ?>"><?= csrf_field() ?><button class="btn btn--ghost btn--sm">Unmute</button></form>
    </div>
  <?php endforeach; ?>
</section>

<section class="panel panel--privacy">
  <h2 class="panel__title">What we collect</h2>
  <p>EMChat stores only what you give us: your email (to sign in), your posts, and who you follow. We use basic Google Analytics to see visit counts, no advertising SDKs run on this site. <a href="<?= e(url('/privacy')) ?>">Read the full policy</a></p>
</section>
