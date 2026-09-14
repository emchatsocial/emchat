<?php
/** @var array $user  @var int|null $replyTo  @var string $placeholder */
$replyTo = $replyTo ?? null;
$placeholder = $placeholder ?? "What's on your mind?";
?>
<form class="composer" method="post" action="<?= e(url('/posts')) ?>" enctype="multipart/form-data" data-ajax="compose">
  <?= csrf_field() ?>
  <?php if ($replyTo): ?><input type="hidden" name="reply_to" value="<?= (int) $replyTo ?>"><?php endif; ?>
  <?= $view->partial('avatar', ['u' => $user, 'size' => 'md']) ?>
  <div class="composer__fields">
    <label class="sr-only" for="composer-body">Write a post</label>
    <textarea id="composer-body" name="body" rows="2" maxlength="2000" placeholder="<?= e($placeholder) ?>" data-autogrow></textarea>
    <div class="composer__previews" data-previews hidden></div>
    <div class="composer__bar">
      <label class="btn btn--icon" title="Add photos">
        <?= icon('image', '', 19) ?><input type="file" name="images[]" accept="image/*" multiple hidden data-image-input>
      </label>
      <select name="visibility" class="composer__vis" aria-label="Who can see this">
        <option value="public">Public</option>
        <option value="followers">Followers</option>
        <option value="private">Only me</option>
      </select>
      <span class="composer__count" data-count>2000</span>
      <button class="btn btn--primary" type="submit"><?= $replyTo ? 'Reply' : 'Post' ?></button>
    </div>
  </div>
</form>
