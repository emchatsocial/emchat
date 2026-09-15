<?php
/** @var string $content  @var array $meta  @var array $admin  @var string $section */
$admin = $admin ?? require_admin();
$pendingReports = \App\Models\Report::countPending();
$nav = [
    'dashboard' => ['/admin', 'shield', 'Dashboard'],
    'users'     => ['/admin/users', 'users', 'Users'],
    'reports'   => ['/admin/reports', 'flag', 'Reports'],
];
?><!doctype html>
<html lang="en" data-theme="dark">
<head><?= $view->renderPartial('partials/head', ['meta' => $meta ?? []]) ?></head>
<body class="admin">
<a class="skip-link" href="#main">Skip to content</a>
<div class="admin-shell">
  <aside class="admin-nav">
    <a class="admin-nav__brand brand-wordmark" href="<?= e(url('/admin')) ?>">EMChat<span class="brand-wordmark__soft"> Admin</span></a>
    <nav class="admin-nav__links">
      <?php foreach ($nav as $key => [$href, $ic, $label]): ?>
        <a href="<?= e(url($href)) ?>" class="<?= $section === $key ? 'is-active' : '' ?>"><?= icon($ic, '', 18) ?> <?= e($label) ?>
          <?php if ($key === 'reports' && $pendingReports > 0): ?><span class="admin-nav__badge"><?= $pendingReports ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-nav__foot">
      <a href="<?= e(url('/feed')) ?>"><?= icon('arrow-left', '', 16) ?> Back to EMChat</a>
      <span class="admin-nav__who">Signed in as <strong><?= e($admin['display_name']) ?></strong></span>
    </div>
  </aside>
  <main id="main" class="admin-main" tabindex="-1">
    <?= $view->renderPartial('partials/flash') ?>
    <?= $content ?>
  </main>
</div>
<div id="toasts" class="toasts" aria-live="polite" aria-atomic="false"></div>
<script src="/assets/js/app.js?v=70" defer></script>
</body>
</html>
