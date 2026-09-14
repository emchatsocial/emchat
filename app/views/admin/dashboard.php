<?php /** @var array $users  @var int $posts  @var int $messages  @var int $pending_reports */ ?>
<div class="page-head"><h1>Dashboard</h1></div>

<div class="admin-stats">
  <div class="admin-stat">
    <span class="admin-stat__label">Total users</span>
    <span class="admin-stat__value"><?= number_format($users['total']) ?></span>
    <span class="admin-stat__sub"><?= number_format($users['new_week']) ?> new this week</span>
  </div>
  <div class="admin-stat">
    <span class="admin-stat__label">Posts</span>
    <span class="admin-stat__value"><?= number_format($posts) ?></span>
  </div>
  <div class="admin-stat">
    <span class="admin-stat__label">Messages sent</span>
    <span class="admin-stat__value"><?= number_format($messages) ?></span>
  </div>
  <div class="admin-stat admin-stat--<?= $pending_reports > 0 ? 'warn' : '' ?>">
    <span class="admin-stat__label">Open reports</span>
    <span class="admin-stat__value"><?= number_format($pending_reports) ?></span>
    <?php if ($pending_reports > 0): ?><a href="<?= e(url('/admin/reports')) ?>">Review now →</a><?php endif; ?>
  </div>
  <div class="admin-stat">
    <span class="admin-stat__label">Admins</span>
    <span class="admin-stat__value"><?= number_format($users['admins']) ?></span>
  </div>
  <div class="admin-stat">
    <span class="admin-stat__label">Suspended accounts</span>
    <span class="admin-stat__value"><?= number_format($users['suspended']) ?></span>
  </div>
</div>

<section class="panel">
  <h2 class="panel__title">Quick links</h2>
  <div class="admin-quicklinks">
    <a class="btn btn--ghost" href="<?= e(url('/admin/users')) ?>"><?= icon('users', '', 16) ?> Manage users</a>
    <a class="btn btn--ghost" href="<?= e(url('/admin/reports')) ?>"><?= icon('flag', '', 16) ?> Review reports</a>
    <a class="btn btn--ghost" href="<?= e(url('/transparency')) ?>" target="_blank" rel="noopener"><?= icon('globe', '', 16) ?> View transparency page</a>
  </div>
</section>
