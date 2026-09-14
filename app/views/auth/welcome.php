<?php /** @var string $email  @var string $suggestion  @var string $name_guess */ ?>
<form class="onboard" method="post" action="<?= e(url('/welcome')) ?>" enctype="multipart/form-data" data-onboard data-ajax="onboard">
  <?= csrf_field() ?>

  <div class="onboard__progress" aria-hidden="true">
    <span class="is-active" data-dot="0"></span>
    <span data-dot="1"></span>
    <span data-dot="2"></span>
  </div>

  <!-- Step 1: handle -->
  <section class="onboard__step is-active" data-step="0">
    <h1 class="auth__title">Claim your handle</h1>
    <p class="auth__sub">This is how people find and mention you. You can change it later.</p>
    <label class="field">
      <span class="field__label">Handle</span>
      <span class="field__prefix onboard__handle">
        <span>@</span>
        <input type="text" name="username" value="<?= e(old('username', $suggestion)) ?>"
               maxlength="30" required autocomplete="off" autocapitalize="none" spellcheck="false"
               pattern="[A-Za-z0-9_]{3,30}" data-username-input autofocus>
        <span class="onboard__status" data-username-status></span>
      </span>
      <span class="field__hint" data-username-hint>3–30 characters · letters, numbers and underscores</span>
    </label>
    <button class="btn btn--primary btn--block btn--lg" type="button" data-next data-requires-username>Continue</button>
  </section>

  <!-- Step 2: name -->
  <section class="onboard__step" data-step="1" hidden>
    <h1 class="auth__title">What's your name?</h1>
    <p class="auth__sub">Shown on your profile and posts. Use whatever you like.</p>
    <label class="field">
      <span class="field__label">Display name</span>
      <input type="text" name="display_name" value="<?= e(old('display_name', $name_guess)) ?>" maxlength="60" placeholder="Your name">
    </label>
    <div class="onboard__nav">
      <button class="btn btn--ghost" type="button" data-back>Back</button>
      <button class="btn btn--primary btn--lg" type="button" data-next>Continue</button>
    </div>
  </section>

  <!-- Step 3: photo + privacy -->
  <section class="onboard__step" data-step="2" hidden>
    <h1 class="auth__title">Add a profile photo</h1>
    <p class="auth__sub">Optional, you can add one any time from settings.</p>

    <label class="onboard__drop" data-avatar-drop>
      <input type="file" name="avatar" accept="image/*" hidden data-avatar-input>
      <span class="onboard__avatar" data-avatar-preview data-initials="<?= e(mb_strtoupper(mb_substr($suggestion, 0, 2))) ?>"><?= e(mb_strtoupper(mb_substr($suggestion, 0, 2))) ?></span>
      <span class="onboard__drop-cta">Choose a photo</span>
    </label>

    <label class="switch onboard__switch">
      <input type="checkbox" name="is_private" value="1">
      <span class="switch__track"></span>
      <span class="switch__label"><strong>Make my account private</strong><small>You approve every follower, and posts stay hidden from the public.</small></span>
    </label>

    <div class="onboard__nav">
      <button class="btn btn--ghost" type="button" data-back>Back</button>
      <button class="btn btn--primary btn--lg" type="submit" data-finish>Finish &amp; enter EMChat</button>
    </div>
  </section>

  <noscript>
    <style>.onboard__step[hidden]{display:block!important}.onboard__progress,.onboard__nav .btn--ghost{display:none}</style>
    <button class="btn btn--primary btn--block btn--lg" type="submit">Create my account</button>
  </noscript>
</form>
