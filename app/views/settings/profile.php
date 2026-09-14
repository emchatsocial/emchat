<?php /** @var array $user  @var string $section */ ?>
<div class="page-head"><h1>Settings</h1></div>
<?= $view->partial('settings_nav', ['section' => $section]) ?>

<div class="settings-avatar">
  <span class="avatar avatar--xl settings-avatar__preview <?= $user['avatar_path'] ? 'has-img' : '' ?>"
        <?= $user['avatar_path'] ? 'style="background-image:url(' . e(media_url($user['avatar_path'])) . ')"' : '' ?>>
    <?= $user['avatar_path'] ? '' : e(initials($user['display_name'] ?: $user['username'])) ?>
  </span>
  <label class="btn btn--ghost btn--sm">Change photo<input type="file" name="avatar" accept="image/*" form="profile-form" hidden></label>
  <?php if ($user['avatar_path']): ?>
    <form method="post" action="<?= e(url('/settings/profile/avatar/remove')) ?>" data-ajax="remove-avatar" data-confirm="Remove your profile photo?">
      <?= csrf_field() ?>
      <button class="btn btn--ghost btn--danger btn--sm" type="submit">Remove photo</button>
    </form>
  <?php endif; ?>
</div>

<form id="profile-form" class="form form--settings" method="post" action="<?= e(url('/settings/profile')) ?>" enctype="multipart/form-data" data-ajax="settings">
  <?= csrf_field() ?>

  <label class="field">
    <span class="field__label">Display name</span>
    <input type="text" name="display_name" maxlength="60" value="<?= e($user['display_name']) ?>">
  </label>
  <label class="field">
    <span class="field__label">Handle</span>
    <span class="field__prefix"><span>@</span>
      <input type="text" name="username" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" value="<?= e($user['username']) ?>">
    </span>
  </label>
  <label class="field">
    <span class="field__label">Bio</span>
    <textarea name="bio" rows="3" maxlength="280" data-autogrow><?= e($user['bio']) ?></textarea>
    <span class="field__hint">Mentions like @name and #tags become links.</span>
  </label>
  <div class="field-row">
    <label class="field">
      <span class="field__label">Location</span>
      <input type="text" name="location" maxlength="80" value="<?= e($user['location']) ?>">
    </label>
    <label class="field">
      <span class="field__label">Website</span>
      <input type="text" name="website" maxlength="190" value="<?= e($user['website']) ?>" placeholder="example.com">
    </label>
  </div>

  <button class="btn btn--primary btn--lg" type="submit">Save changes</button>
</form>
