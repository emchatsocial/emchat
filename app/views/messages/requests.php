<?php /** @var array $threads  @var array $requests  @var mixed $active */ ?>
<div class="messenger">
  <div class="messenger__list">
    <?= $view->partial('thread_list', ['threads' => $threads, 'active' => $active]) ?>
  </div>
  <div class="messenger__panel">
    <header class="messenger__head">
      <a class="back back--inline" href="<?= e(url('/messages')) ?>"><?= icon('arrow-left', '', 18) ?></a>
      <strong>Message requests</strong>
    </header>

    <div class="reqlist">
      <p class="reqlist__note">
        These people aren't followed by you, so their messages wait here. Accepting
        moves the chat to your inbox; deleting removes it and they aren't told.
      </p>

      <?php if (!$requests): ?>
        <div class="empty">
          <div class="empty__icon"><?= icon('check', '', 34) ?></div>
          <h2>You're all caught up</h2>
          <p>No message requests right now.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($requests as $t): ?>
        <?php $isGroup = !empty($t['is_group']); $name = $isGroup ? ($t['title'] ?: 'Group') : $t['display_name']; ?>
        <div class="reqrow">
          <a class="reqrow__main" href="<?= e(url('/messages/' . $t['id'])) ?>">
            <?php if ($isGroup): ?>
              <span class="avatar avatar--md avatar--fallback" aria-hidden="true"><?= icon('users', '', 18) ?></span>
            <?php else: ?>
              <?= $view->partial('avatar', ['u' => $t, 'size' => 'md']) ?>
            <?php endif; ?>
            <span class="reqrow__id">
              <span class="reqrow__name"><?= e($name) ?><?php if (!$isGroup): ?> <span class="reqrow__handle">@<?= e($t['username']) ?></span><?php endif; ?></span>
              <span class="reqrow__preview"><?= e(mb_strimwidth((string) $t['last_body'], 0, 70, '…')) ?></span>
            </span>
          </a>
          <div class="reqrow__actions">
            <form method="post" action="<?= e(url('/messages/' . $t['id'] . '/accept')) ?>" data-group-action>
              <?= csrf_field() ?>
              <button class="btn btn--primary btn--sm" type="submit">Accept</button>
            </form>
            <form method="post" action="<?= e(url('/messages/' . $t['id'] . '/decline')) ?>"
                  data-group-action data-confirm="Delete this message request from <?= e($name) ?>?">
              <?= csrf_field() ?>
              <button class="btn btn--ghost btn--sm" type="submit">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
