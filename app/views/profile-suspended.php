<?php /** @var array $profile  @var bool $is_admin_viewer */ ?>
<section class="wrap errorpage">
  <p class="errorpage__code"><?= icon('ban', '', 48) ?></p>
  <h1>@<?= e($profile['username']) ?>'s account has been suspended</h1>
  <p>Their posts and profile aren't available while their account is suspended.</p>
  <?php if (!empty($is_admin_viewer)): ?>
    <p><a class="btn btn--ghost" href="<?= e(url('/admin/users')) ?>">Manage in Admin</a></p>
  <?php else: ?>
    <p><a class="btn btn--primary" href="<?= e(url('/')) ?>">Back to home</a></p>
  <?php endif; ?>
</section>
