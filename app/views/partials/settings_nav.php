<?php /** @var string $section */ ?>
<nav class="tabs tabs--settings">
  <a class="tab <?= $section === 'profile' ? 'is-active' : '' ?>" href="<?= e(url('/settings/profile')) ?>">Profile</a>
  <a class="tab <?= $section === 'privacy' ? 'is-active' : '' ?>" href="<?= e(url('/settings/privacy')) ?>">Privacy &amp; safety</a>
  <a class="tab <?= $section === 'account' ? 'is-active' : '' ?>" href="<?= e(url('/settings/account')) ?>">Account</a>
</nav>
