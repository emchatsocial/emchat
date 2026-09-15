<?php
/** @var string $content  @var array $meta */
?><!doctype html>
<html lang="en" data-theme="dark">
<head><?= $view->renderPartial('partials/head', ['meta' => $meta ?? []]) ?></head>
<body class="auth">
<div class="auth__grid">
  <aside class="auth__brandpane" aria-hidden="true">
    <div class="auth__noise"></div>
    <div class="auth__brandpane-inner">
      <a class="auth__brand brand-wordmark brand-wordmark--light" href="<?= e(url('/')) ?>">EMChat<span class="brand-wordmark__soft"> Media</span></a>
      <h2 class="auth__pitch">A social&nbsp;network that&nbsp;keeps<span class="auth__pitch-em">your&nbsp;world&nbsp;yours.</span></h2>
      <p class="auth__pitchsub">Posts, photos and private messages with no ad trackers, no behavioural profiling, and a feed that stays in order.</p>
      <ul class="auth__pitchlist">
        <li><span class="tick"><?= icon('check', '', 12) ?></span> Passwordless sign in, nothing to leak</li>
        <li><span class="tick"><?= icon('check', '', 12) ?></span> Photos cleared of location data on upload</li>
        <li><span class="tick"><?= icon('check', '', 12) ?></span> Export or delete everything in one click</li>
      </ul>
    </div>
    <div class="auth__glow auth__glow--1"></div>
    <div class="auth__glow auth__glow--2"></div>
  </aside>

  <main id="main" class="auth__formpane">
    <div class="auth__formwrap">
      <a class="auth__brand auth__brand--mobile brand-wordmark" href="<?= e(url('/')) ?>">EMChat<span class="brand-wordmark__soft"> Media</span></a>
      <div class="auth__card">
        <span class="auth__card-accent" aria-hidden="true"></span>
        <?= $view->renderPartial('partials/flash') ?>
        <?= $content ?>
      </div>
      <p class="auth__legal">
        By continuing you agree to our <a href="<?= e(url('/terms')) ?>">Terms</a>
        and <a href="<?= e(url('/privacy')) ?>">Privacy&nbsp;Policy</a>.
      </p>
    </div>
  </main>
</div>
<div id="toasts" class="toasts" aria-live="polite"></div>
<script src="/assets/js/app.js?v=70" defer></script>
</body>
</html>
