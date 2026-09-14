<?php /** @var array $threads  @var mixed $active */ ?>
<div class="messenger messenger--list-only">
  <div class="messenger__list">
    <?= $view->partial('thread_list', ['threads' => $threads, 'active' => $active]) ?>
  </div>
  <div class="messenger__panel messenger__panel--empty">
    <div class="empty">
      <div class="empty__icon"><?= icon('send', '', 40) ?></div>
      <h2>Your messages</h2>
      <p>Send private messages, photos and files to people on EMChat.</p>
      <a class="btn btn--primary" href="<?= e(url('/messages/new')) ?>">Send message</a>
    </div>
  </div>
</div>
