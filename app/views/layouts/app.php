<?php
/** @var string $content  @var array $meta */
$user = current_user();
if (!$user) { redirect('/login'); }
?><!doctype html>
<html lang="en" data-theme="dark">
<head><?= $view->renderPartial('partials/head', ['meta' => $meta ?? []]) ?></head>
<body class="app">
<a class="skip-link" href="#main">Skip to content</a>
<div class="shell">
  <aside class="shell__nav"><?= $view->partial('nav', ['user' => $user]) ?></aside>
  <main id="main" class="shell__main" tabindex="-1">
    <?= $view->renderPartial('partials/flash') ?>
    <?= $content ?>
  </main>
  <div class="shell__aside">
    <?= $view->renderPartial('partials/aside', ['user' => $user]) ?>
  </div>
</div>

<div class="composer-modal" id="composer" hidden>
  <div class="composer-modal__panel">
    <button class="composer-modal__close" data-close-composer aria-label="Close"><?= icon('x','',16) ?></button>
    <?= $view->partial('composer', ['user' => $user]) ?>
  </div>
</div>

<?php $tp = \App\Request::path(); ?>
<nav class="tabbar" aria-label="Primary mobile">
  <a href="<?= e(url('/feed')) ?>" aria-label="Home" class="<?= str_starts_with($tp, '/feed') ? 'is-active' : '' ?>"><?= icon('home', '', 23) ?></a>
  <a href="<?= e(url('/explore')) ?>" aria-label="Explore" class="<?= str_starts_with($tp, '/explore') ? 'is-active' : '' ?>"><?= icon('compass', '', 23) ?></a>
  <button data-open-composer aria-label="New post" class="tabbar__post"><?= icon('plus', '', 24) ?></button>
  <a href="<?= e(url('/notifications')) ?>" aria-label="Notifications" data-badge="notifications" class="<?= str_starts_with($tp, '/notifications') ? 'is-active' : '' ?>"><?= icon('bell', '', 23) ?></a>
  <a href="<?= e(url('/messages')) ?>" aria-label="Messages" data-badge="messages" class="<?= str_starts_with($tp, '/messages') ? 'is-active' : '' ?>"><?= icon('mail', '', 23) ?></a>
  <a href="<?= e(url('@' . $user['username'])) ?>" aria-label="Profile" class="tabbar__profile <?= str_starts_with($tp, '/@' . $user['username']) ? 'is-active' : '' ?>"><?= $view->partial('avatar', ['u' => $user, 'size' => 'xs']) ?></a>
</nav>

<div id="toasts" class="toasts" aria-live="polite" aria-atomic="false"></div>

<script src="/assets/js/app.js?v=70" defer></script>
</body>
</html>
