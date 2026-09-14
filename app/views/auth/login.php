<div class="auth__head">
  <h1 class="auth__title">Sign in to EMChat</h1>
  <p class="auth__sub">One box for both signing in and creating an account. We'll email you a single-use link, no password to set or remember.</p>
</div>

<form method="post" action="<?= e(url('/login')) ?>" class="form auth__form" novalidate>
  <?= csrf_field() ?>
  <label class="field field--icon">
    <span class="field__label">Email address</span>
    <span class="field__wrap">
      <span class="field__ico"><?= icon('mail', '', 18) ?></span>
      <input type="email" name="email" inputmode="email" autocomplete="email" required
             autofocus value="<?= e(old('email')) ?>" placeholder="you@example.com">
    </span>
  </label>
  <div class="field field--hp" aria-hidden="true">
    <label>Leave this blank<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
  </div>
  <button class="btn btn--primary btn--block btn--lg" type="submit">
    Email me a sign-in link <?= icon('arrow-right', '', 16) ?>
  </button>
</form>

<div class="auth__rule"><span>Why passwordless</span></div>

<ul class="auth__points">
  <li><span class="tick"><?= icon('check', '', 12) ?></span> Nothing to remember, and nothing to steal in a breach</li>
  <li><span class="tick"><?= icon('check', '', 12) ?></span> The link works once and expires after 15 minutes</li>
  <li><span class="tick"><?= icon('check', '', 12) ?></span> We never sell or share your email address</li>
</ul>
