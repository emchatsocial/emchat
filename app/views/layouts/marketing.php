<?php
/** @var string $content  @var array $meta */
$user = current_user();
?><!doctype html>
<html lang="en" data-theme="dark">
<head><?= $view->renderPartial('partials/head', ['meta' => $meta ?? []]) ?></head>
<body class="marketing">
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
  <div class="wrap site-header__inner">
    <a class="site-header__brand brand-wordmark" href="<?= e(url('/')) ?>">EMChat<span class="brand-wordmark__soft"> Media</span></a>
    <nav class="site-header__nav">
      <a href="<?= e(url('/explore')) ?>">Explore</a>
      <a href="<?= e(url('/about')) ?>">About</a>
      <a href="<?= e(url('/privacy')) ?>">Privacy</a>
      <?php if ($user): ?>
        <a class="btn btn--primary" href="<?= e(url('/feed')) ?>">Open EMChat</a>
      <?php else: ?>
        <a class="btn btn--primary" href="<?= e(url('/login')) ?>">Log in or sign up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main id="main" tabindex="-1">
  <div class="wrap"><?= $view->renderPartial('partials/flash') ?></div>
  <?= $content ?>
</main>
<footer class="site-footer">
  <div class="wrap site-footer__inner">
    <div class="site-footer__brand">
      <strong><?= e(config('app_name')) ?></strong>
      <p><?= e(config('app_tagline')) ?></p>
    </div>
    <nav>
      <span class="site-footer__label">Product</span>
      <a href="<?= e(url('/explore')) ?>">Explore</a>
      <a href="<?= e(url('/about')) ?>">About</a>
      <a href="<?= e(url('/login')) ?>">Log in</a>
    </nav>
    <nav>
      <span class="site-footer__label">Legal</span>
      <a href="<?= e(url('/privacy')) ?>">Privacy Policy</a>
      <a href="<?= e(url('/terms')) ?>">Terms</a>
      <a href="<?= e(url('/transparency')) ?>">Transparency</a>
    </nav>
    <nav>
      <span class="site-footer__label">Contact</span>
      <a href="mailto:egzon@emchat.social">egzon@emchat.social</a>
    </nav>
    <p class="site-footer__legal">&copy; <?= date('Y') ?> <?= e(config('app_name')) ?> &middot; Made independently in North Macedonia</p>
  </div>
</footer>
<div id="toasts" class="toasts" aria-live="polite"></div>
<script src="/assets/js/app.js?v=71" defer></script>
</body>
</html>
