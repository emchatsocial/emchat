<?php /** @var string $section */ ?>
<nav class="tabs tabs--settings">
  <a class="tab <?= $section === 'profile' ? 'is-active' : '' ?>" href="<?= e(url('/settings/profile')) ?>">Profile</a>
  <a class="tab <?= $section === 'privacy' ? 'is-active' : '' ?>" href="<?= e(url('/settings/privacy')) ?>">Privacy &amp; safety</a>
  <?php if (\App\Models\E2E::enabledFor(current_user())): ?>
  <a class="tab <?= $section === 'encryption' ? 'is-active' : '' ?>" href="<?= e(url('/settings/encryption')) ?>">Encryption</a>
  <?php endif; ?>
  <a class="tab <?= $section === 'account' ? 'is-active' : '' ?>" href="<?= e(url('/settings/account')) ?>">Account</a>
</nav>
