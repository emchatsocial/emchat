<?php /** @var array $items  @var array $requests  @var array $viewer */
$verb = [
    'like' => 'liked your post', 'follow' => 'followed you',
    'follow_request' => 'requested to follow you', 'comment' => 'replied to your post',
    'mention' => 'mentioned you', 'message' => 'sent you a message',
];
?>
<div class="page-head"><h1>Notifications</h1></div>

<?php if ($requests): ?>
  <section class="panel">
    <h2 class="panel__title">Follow requests</h2>
    <?php foreach ($requests as $r): ?>
      <div class="notif notif--request">
        <a href="<?= e(url('@' . $r['username'])) ?>" aria-label="<?= e($r['display_name']) ?>'s profile"><?= $view->partial('avatar', ['u' => $r, 'size' => 'sm']) ?></a>
        <div class="notif__text">
          <a href="<?= e(url('@' . $r['username'])) ?>"><strong><?= e($r['display_name']) ?></strong></a>
          <span class="muted">@<?= e($r['username']) ?> wants to follow you</span>
        </div>
        <div class="notif__cta">
          <form method="post" action="<?= e(url('/x/approve/' . $r['username'])) ?>" data-ajax="request"><?= csrf_field() ?><button class="btn btn--primary btn--sm">Accept</button></form>
          <form method="post" action="<?= e(url('/x/deny/' . $r['username'])) ?>" data-ajax="request"><?= csrf_field() ?><button class="btn btn--ghost btn--sm">Decline</button></form>
        </div>
      </div>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<section class="notif-list">
  <?php if (!$items): ?>
    <div class="empty"><h2>Nothing yet</h2><p>Likes, follows, replies, and mentions will appear here.</p></div>
  <?php else: ?>
    <?php foreach ($items as $n): ?>
      <?php
        $href = match ($n['type']) {
            'follow', 'follow_request' => url('@' . $n['username']),
            'message' => url('/messages/' . (int) $n['subject_id']),
            default => $n['subject_id'] ? url('/p/' . (int) $n['subject_id']) : '#',
        };
      ?>
      <a class="notif <?= $n['read_at'] ? '' : 'is-unread' ?>" href="<?= e($href) ?>">
        <?= $view->partial('avatar', ['u' => $n, 'size' => 'sm']) ?>
        <div class="notif__text">
          <strong><?= e($n['display_name']) ?></strong>
          <span><?= e($verb[$n['type']] ?? 'did something') ?></span>
          <time><?= e(time_ago($n['created_at'])) ?></time>
        </div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
