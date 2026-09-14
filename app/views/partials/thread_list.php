<?php
/** @var array $threads  @var mixed $active */
$me = current_user();
$reqCount = \App\Models\Messaging::requestCount((int) ($me['id'] ?? 0));
?>
<div class="threads">
  <div class="threads__head">
    <h2><?= e('@' . ($me['username'] ?? 'you')) ?></h2>
    <div class="threads__head-actions">
      <a class="btn btn--icon" href="<?= e(url('/messages/new/group')) ?>" title="New group" aria-label="New group"><?= icon('users', '', 19) ?></a>
      <a class="btn btn--icon" href="<?= e(url('/messages/new')) ?>" title="New message" aria-label="New message"><?= icon('pencil', '', 19) ?></a>
    </div>
  </div>

  <?php if ($reqCount > 0 || $active === 'requests'): ?>
    <a class="threadrow threadrow--requests <?= $active === 'requests' ? 'is-active' : '' ?>" href="<?= e(url('/messages/requests')) ?>">
      <span class="avatar avatar--md avatar--fallback" aria-hidden="true"><?= icon('mail', '', 19) ?></span>
      <div class="threadrow__body">
        <div class="threadrow__top"><strong>Message requests</strong></div>
        <p class="threadrow__preview"><?= $reqCount > 0 ? (int) $reqCount . ' waiting for your reply' : 'None right now' ?></p>
      </div>
      <?php if ($reqCount > 0): ?><span class="threadrow__count"><?= (int) $reqCount ?></span><?php endif; ?>
    </a>
  <?php endif; ?>

  <?php if (!$threads): ?>
    <p class="threads__empty">No messages yet. Start a chat from someone's profile.</p>
  <?php endif; ?>

  <?php foreach ($threads as $t): ?>
    <?php
      $isGroup = !empty($t['is_group']);
      $name = $isGroup ? ($t['title'] ?: 'Group') : $t['display_name'];
      $sys  = ($t['last_kind'] ?? '') === 'system';
      $meLast = !$sys && (int) ($t['last_sender_id'] ?? 0) === (int) ($me['id'] ?? 0);
      if ($sys) {
          $preview = trim(($t['last_sender_name'] ?? '') . ' ' . (string) $t['last_body']);
      } elseif ($meLast) {
          $preview = 'You: ' . (string) $t['last_body'];
      } elseif ($isGroup && !empty($t['last_sender_name'])) {
          $preview = explode(' ', trim((string) $t['last_sender_name']))[0] . ': ' . (string) $t['last_body'];
      } else {
          $preview = (string) $t['last_body'];
      }
    ?>
    <div class="threadrow <?= (string) $t['id'] === (string) $active ? 'is-active' : '' ?> <?= $t['unread'] > 0 ? 'is-unread' : '' ?>">
      <a class="threadrow__hit" href="<?= e(url('/messages/' . $t['id'])) ?>" aria-label="Open chat with <?= e($name) ?>"></a>
      <?php if ($isGroup && !empty($t['photo_path'])): ?>
        <img class="avatar avatar--md" src="<?= e(media_url($t['photo_path'])) ?>" alt="" loading="lazy" width="96" height="96">
        <span class="avatar avatar--md avatar--fallback" aria-hidden="true" hidden><?= icon('users', '', 18) ?></span>
      <?php elseif ($isGroup): ?>
        <span class="avatar avatar--md avatar--fallback" aria-hidden="true"><?= icon('users', '', 18) ?></span>
      <?php else: ?>
        <?= $view->partial('avatar', ['u' => $t, 'size' => 'md']) ?>
      <?php endif; ?>
      <div class="threadrow__body">
        <div class="threadrow__top">
          <strong><?= e($name) ?></strong>
          <?php if ($t['last_message_at']): ?><time><?= e(time_ago($t['last_message_at'])) ?></time><?php endif; ?>
        </div>
        <p class="threadrow__preview <?= $sys ? 'threadrow__preview--sys' : '' ?>"><?= e(mb_strimwidth($preview, 0, 46, '…')) ?></p>
      </div>
      <?php if ($t['unread'] > 0): ?><span class="threadrow__dot"></span><?php endif; ?>
      <details class="threadrow__menu">
        <summary aria-label="Chat options"><?= icon('more', '', 18) ?></summary>
        <div class="menu">
          <form method="post" action="<?= e(url('/messages/' . $t['id'] . '/delete')) ?>"
                data-group-action data-confirm="Delete your copy of this chat? It comes back if a new message arrives.">
            <?= csrf_field() ?>
            <button class="menu__item menu__item--danger" type="submit"><?= icon('trash') ?> Delete chat</button>
          </form>
        </div>
      </details>
    </div>
  <?php endforeach; ?>
</div>
