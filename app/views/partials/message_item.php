<?php
/** @var array $m  @var array $user  @var bool $isGroup  @var bool $runStart  @var bool $runEnd */
$isGroup  = $isGroup ?? false;
$runStart = $runStart ?? true;
$runEnd   = $runEnd ?? true;
$created  = strtotime($m['created_at']);

if (($m['kind'] ?? 'text') === 'system'):
    $who = trim((string) ($m['display_name'] ?? ''));
    ?>
    <div class="msg-system" data-mid="<?= (int) $m['id'] ?>" data-created="<?= $created ?>"
         data-day="<?= e(date('Y-m-d', $created)) ?>">
      <span><?= e(trim($who . ' ' . (string) $m['body'])) ?></span>
    </div>
    <?php return; endif;

$mine    = (int) $m['sender_id'] === (int) $user['id'];
$deleted = !empty($m['deleted_at']);
$edited  = !empty($m['edited_at']) && !$deleted;
$canEdit = $mine && !$deleted && $created > time() - \App\Models\Messaging::EDIT_WINDOW;
$att     = $deleted ? [] : ($m['attachments'] ?? []);
$images  = array_values(array_filter($att, fn ($a) => $a['kind'] === 'image'));
$others  = array_values(array_filter($att, fn ($a) => $a['kind'] !== 'image'));
$hasText = trim((string) $m['body']) !== '';
$cid     = (int) $m['conversation_id'];
$reply   = $m['reply'] ?? null;
$plain   = trim(preg_replace('/\s+/', ' ', (string) $m['body']));
$showAva = $isGroup && !$mine;

$cls = $mine ? 'msg--out' : 'msg--in';
if ($deleted)   $cls .= ' msg--gone';
if ($runStart)  $cls .= ' msg--run-start';
if ($runEnd)    $cls .= ' msg--run-end';
?>
<div class="msg <?= $cls ?>"
     data-mid="<?= (int) $m['id'] ?>" data-mine="<?= $mine ? '1' : '0' ?>"
     data-created="<?= $created ?>" data-day="<?= e(date('Y-m-d', $created)) ?>">

  <?php if (!$deleted): ?>
    <details class="msg__menu">
      <summary aria-label="Message options"><?= icon('more', '', 18) ?></summary>
      <div class="menu">
        <button class="menu__item" type="button" data-msg-reply
                data-name="<?= e($m['display_name']) ?>"
                data-snippet="<?= e(mb_strimwidth($plain !== '' ? $plain : ($images ? 'Photo' : 'Attachment'), 0, 90, '…')) ?>"><?= icon('reply') ?> Reply</button>
        <?php if ($hasText): ?>
          <button class="menu__item" type="button" data-copy="<?= e($m['body']) ?>"><?= icon('copy') ?> Copy</button>
        <?php endif; ?>
        <?php if ($canEdit): ?>
          <button class="menu__item" type="button" data-msg-edit><?= icon('pencil') ?> Edit</button>
        <?php endif; ?>
        <?php if ($mine): ?>
          <button class="menu__item menu__item--danger" type="button"
                  data-msg-delete data-scope="choice"
                  data-url="<?= e(url("/messages/{$cid}/m/{$m['id']}/delete")) ?>"><?= icon('trash') ?> Unsend</button>
        <?php else: ?>
          <button class="menu__item menu__item--danger" type="button"
                  data-msg-delete data-scope="me"
                  data-url="<?= e(url("/messages/{$cid}/m/{$m['id']}/delete")) ?>"><?= icon('trash') ?> Delete</button>
          <form method="post" action="<?= e(url("/messages/{$cid}/m/{$m['id']}/report")) ?>" data-ajax="report" data-report-label="message">
            <?= csrf_field() ?><input type="hidden" name="reason" value="other">
            <button class="menu__item menu__item--danger" type="submit"><?= icon('flag') ?> Report</button>
          </form>
        <?php endif; ?>
      </div>
    </details>
  <?php endif; ?>

  <?php if ($showAva): ?>
    <span class="msg__ava<?= $runEnd ? '' : ' msg__ava--spacer' ?>">
      <?php if ($runEnd): ?><?= $view->partial('avatar', ['u' => $m, 'size' => 'xs']) ?><?php endif; ?>
    </span>
  <?php endif; ?>

  <div class="msg__stack">
    <?php if ($showAva && $runStart && !$deleted): ?>
      <a class="msg__sender" href="<?= e(url('@' . $m['username'])) ?>"><?= e($m['display_name']) ?></a>
    <?php endif; ?>

    <?php if ($reply): ?>
      <div class="msg__replyto">
        <span class="msg__replyto-name"><?= e($reply['display_name']) ?></span>
        <span class="msg__replyto-text"><?= $reply['deleted_at'] ? '<em>original message deleted</em>' : e(mb_strimwidth(trim((string) $reply['body']), 0, 90, '…')) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($deleted): ?>
      <div class="msg__bubble msg__bubble--gone"><?= $mine ? 'You unsent a message' : 'This message was deleted' ?></div>
    <?php else: ?>
      <?php if ($images): ?>
        <div class="msg__images msg__images--<?= min(count($images), 4) ?>">
          <?php foreach ($images as $img): ?>
            <a class="msg__img" href="<?= e(media_url($img['path'])) ?>" target="_blank" rel="noopener">
              <img src="<?= e(media_url($img['path'])) ?>" alt="<?= e($img['name']) ?>" loading="lazy"
                   <?= $img['width'] ? 'width="' . (int) $img['width'] . '" height="' . (int) $img['height'] . '"' : '' ?>>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php foreach ($others as $a): ?>
        <?php if ($a['kind'] === 'video'): ?>
          <video class="msg__video" controls preload="metadata" src="<?= e(media_url($a['path'])) ?>"></video>
        <?php elseif ($a['kind'] === 'audio'): ?>
          <audio class="msg__audio" controls preload="metadata" src="<?= e(media_url($a['path'])) ?>"></audio>
        <?php else: ?>
          <a class="msg__file" href="<?= e(media_url($a['path'])) ?>" download="<?= e($a['name']) ?>" target="_blank" rel="noopener">
            <span class="msg__file-ico"><?= icon('paperclip', '', 18) ?></span>
            <span class="msg__file-meta">
              <span class="msg__file-name"><?= e($a['name'] ?: 'file') ?></span>
              <span class="msg__file-size"><?= e(\App\Upload::humanSize((int) $a['size'])) ?></span>
            </span>
            <span class="msg__file-dl"><?= icon('download', '', 16) ?></span>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>

      <?php if ($hasText): ?>
        <div class="msg__bubble" data-msg-body><?= rich_text($m['body']) ?></div>
      <?php endif; ?>

      <?php if ($mine): ?>
        <form class="msg__edit" method="post" action="<?= e(url("/messages/{$cid}/m/{$m['id']}/edit")) ?>" data-msg-edit-form hidden>
          <?= csrf_field() ?>
          <textarea name="body" rows="1" data-autogrow><?= e($m['body']) ?></textarea>
          <div class="msg__edit-actions">
            <button class="btn btn--ghost btn--sm" type="button" data-msg-edit-cancel>Cancel</button>
            <button class="btn btn--primary btn--sm" type="submit">Save</button>
          </div>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <time class="msg__time" datetime="<?= e(date('c', $created)) ?>">
    <?= e(date('g:i A', $created)) ?><?php if ($edited): ?> &middot; Edited<?php endif; ?>
  </time>
</div>
