<?php /** @var ?array $viewer */ $user = current_user(); ?>
<div class="<?= $user ? '' : 'wrap ' ?>contact">
  <div class="page-head">
    <h1>Contact</h1>
  </div>
  <p class="muted" style="margin:0 0 20px">Questions, feedback, or anything else: send a message below, or email <a href="mailto:egzon@emchat.social">egzon@emchat.social</a> directly.</p>

  <form method="post" action="<?= e(url('/contact')) ?>" class="form" novalidate>
    <?= csrf_field() ?>
    <label class="field field--icon">
      <span class="field__label">Your email</span>
      <span class="field__wrap">
        <span class="field__ico"><?= icon('mail', '', 18) ?></span>
        <input type="email" name="email" inputmode="email" autocomplete="email" required
               value="<?= e(old('email') ?: ($user['email'] ?? '')) ?>" placeholder="you@example.com">
      </span>
    </label>
    <label class="field">
      <span class="field__label">Message</span>
      <textarea name="message" rows="6" maxlength="4000" required placeholder="What's on your mind?"><?= e(old('message')) ?></textarea>
    </label>
    <div class="field field--hp" aria-hidden="true">
      <label>Leave this blank<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
    </div>
    <button class="btn btn--primary btn--lg" type="submit">Send message</button>
  </form>
</div>
