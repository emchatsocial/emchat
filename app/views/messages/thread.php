<?php
/** @var array $threads  @var array $messages  @var ?array $other  @var ?array $group
 *  @var bool $is_group  @var bool $is_request  @var ?string $seen_at  @var int $cid
 *  @var mixed $active  @var int $last_id */
$user = current_user();
$is_group   = $is_group ?? false;
$is_request = $is_request ?? false;
$group      = $group ?? null;
$seen_at    = $seen_at ?? null;
$peerName   = $is_group ? ($group['title'] ?? 'Group') : ($other['display_name'] ?? 'Chat');

// "Seen" under the last outgoing message
$seenLine = false;
if ($seen_at && $messages) {
    $last = end($messages);
    if ((int) $last['sender_id'] === (int) $user['id'] && empty($last['deleted_at'])
        && strtotime($seen_at) >= strtotime($last['created_at'])) {
        $seenLine = true;
    }
}
?>
<div class="messenger">
  <div class="messenger__list">
    <?= $view->partial('thread_list', ['threads' => $threads, 'active' => $active]) ?>
  </div>
  <div class="messenger__panel" data-conversation="<?= (int) $cid ?>"
       data-poll-url="<?= e(url('/messages/' . $cid)) ?>" data-last-id="<?= (int) $last_id ?>">
    <header class="messenger__head">
      <a class="back--inline" href="<?= e(url('/messages')) ?>" aria-label="Back to messages"><?= icon('arrow-left', '', 20) ?></a>

      <?php if ($is_group): ?>
        <a class="messenger__peer" href="<?= e(url('/messages/' . $cid . '/info')) ?>">
          <?php if (!empty($group['photo_path'])): ?>
            <img class="avatar" src="<?= e(media_url($group['photo_path'])) ?>" alt="" width="96" height="96">
            <span class="avatar avatar--fallback" aria-hidden="true" hidden><?= icon('users', '', 18) ?></span>
          <?php else: ?>
            <span class="avatar avatar--fallback" aria-hidden="true"><?= icon('users', '', 18) ?></span>
          <?php endif; ?>
          <span><strong><?= e($group['title'] ?: 'Group') ?></strong><small><?= (int) $group['member_count'] ?> members</small></span>
        </a>
      <?php elseif ($other): ?>
        <a class="messenger__peer" href="<?= e(url('@' . $other['username'])) ?>">
          <?= $view->partial('avatar', ['u' => $other, 'size' => 'md']) ?>
          <span><strong><?= e($other['display_name']) ?></strong><small>@<?= e($other['username']) ?></small></span>
        </a>
      <?php endif; ?>

      <div class="messenger__headtools">
        <?php if ($is_group): ?>
          <a class="btn btn--icon" href="<?= e(url('/messages/' . $cid . '/info')) ?>" title="Group info" aria-label="Group info"><?= icon('info', '', 19) ?></a>
        <?php endif; ?>
        <details class="messenger__menu">
          <summary aria-label="Chat options"><?= icon('more', '', 20) ?></summary>
          <div class="menu">
            <?php if ($is_group): ?>
              <a class="menu__item" href="<?= e(url('/messages/' . $cid . '/info')) ?>"><?= icon('info') ?> Group info</a>
            <?php elseif ($other): ?>
              <a class="menu__item" href="<?= e(url('@' . $other['username'])) ?>"><?= icon('user') ?> View profile</a>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/messages/' . $cid . '/delete')) ?>"
                  data-group-action data-confirm="Delete your copy of this chat? It comes back if a new message arrives.">
              <?= csrf_field() ?>
              <button class="menu__item menu__item--danger" type="submit"><?= icon('trash') ?> Delete chat</button>
            </form>
            <?php if ($is_group): ?>
              <form method="post" action="<?= e(url('/messages/' . $cid . '/leave')) ?>"
                    data-group-action data-confirm="Leave this group?">
                <?= csrf_field() ?>
                <button class="menu__item menu__item--danger" type="submit"><?= icon('logout') ?> Leave group</button>
              </form>
            <?php elseif ($other): ?>
              <form method="post" action="<?= e(url('/x/block/' . $other['username'])) ?>" data-ajax="quiet" data-confirm="Block @<?= e($other['username']) ?>? They won't be able to message or find you.">
                <?= csrf_field() ?>
                <button class="menu__item menu__item--danger" type="submit"><?= icon('ban') ?> Block @<?= e($other['username']) ?></button>
              </form>
            <?php endif; ?>
          </div>
        </details>
      </div>
    </header>

    <div class="messenger__scroll" data-messages>
      <?= $view->partial('messages_list', ['messages' => $messages, 'user' => $user, 'isGroup' => $is_group]) ?>
      <?php if ($seenLine): ?><div class="dmt__seen" data-seen>Seen</div><?php endif; ?>
    </div>

    <?php if ($is_request): ?>
      <div class="reqbar">
        <p class="reqbar__text"><strong><?= e($peerName) ?></strong> wants to send you <?= $is_group ? 'a group message' : 'a message' ?>. Accept to reply, or delete it &mdash; <?= $is_group ? 'the group' : 'they' ?> won't be told.</p>
        <div class="reqbar__actions">
          <form method="post" action="<?= e(url('/messages/' . $cid . '/accept')) ?>" data-group-action>
            <?= csrf_field() ?><button class="btn btn--primary" type="submit">Accept</button>
          </form>
          <form method="post" action="<?= e(url('/messages/' . $cid . '/decline')) ?>"
                data-group-action data-confirm="Delete this message request from <?= e($peerName) ?>?">
            <?= csrf_field() ?><button class="btn btn--ghost" type="submit">Delete</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <form class="messenger__compose" method="post" action="<?= e(url('/messages/' . $cid)) ?>"
            enctype="multipart/form-data" data-ajax="message">
        <?= csrf_field() ?>
        <input type="hidden" name="reply_to" value="" data-reply-input>
        <div class="messenger__replybar" data-reply-bar hidden>
          <div class="messenger__replybar-body">
            <span class="messenger__replybar-to" data-reply-to></span>
            <span class="messenger__replybar-text" data-reply-text></span>
          </div>
          <button class="messenger__replybar-x" type="button" data-reply-cancel aria-label="Cancel reply"><?= icon('x', '', 14) ?></button>
        </div>
        <div class="messenger__attachments" data-attach-previews hidden></div>
        <div class="messenger__composerow">
          <label class="btn btn--icon" title="Attach photo, video or file">
            <?= icon('image', '', 20) ?><input type="file" name="files[]" multiple hidden data-attach-input
                     accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip">
          </label>
          <label class="sr-only" for="msg-body">Message</label>
          <textarea id="msg-body" name="body" rows="1" placeholder="Message&hellip;" data-autogrow></textarea>
          <button class="messenger__send" type="submit">Send</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
