<?php /** @var string $email  @var ?string $debug_link */ ?>
<div class="auth__icon"><?= icon('mail','',34) ?></div>
<h1 class="auth__title">Check your email</h1>
<p class="auth__sub">
  We sent a sign-in link to <strong><?= e($email) ?></strong>.
  Open it on this device to continue. It expires in 15 minutes.
</p>

<form method="post" action="<?= e(url('/login/resend')) ?>" class="auth__form">
  <?= csrf_field() ?>
  <button class="btn btn--ghost btn--block" type="submit"><?= icon('mail','',15) ?> Resend email</button>
</form>

<p class="auth__meta">
  Wrong address? <a href="<?= e(url('/login')) ?>">Start over</a>.
  Didn't arrive? Check your spam folder.
</p>

<?php if ($debug_link): ?>
  <div class="devbox">
    <p><strong>Local dev only.</strong> Email sending is off, use this link to sign in:</p>
    <a href="<?= e($debug_link) ?>" class="btn btn--primary btn--block">Skip email &amp; sign in</a>
  </div>
<?php endif; ?>
