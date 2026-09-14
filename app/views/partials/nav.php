<?php
/** @var array $user */
use App\Models\Notification;
use App\Models\Messaging;

$uid = (int) $user['id'];
$notif = Notification::unreadCount($uid);
$msgs = Messaging::totalUnread($uid);
$path = \App\Request::path();
$is = fn (string $p) => str_starts_with($path, $p) ? ' is-active' : '';
$items = [
    ['/feed', 'Home', 'home', 0, null],
    ['/explore', 'Explore', 'compass', 0, null],
    ['/notifications', 'Alerts', 'bell', $notif, 'notifications'],
    ['/messages', 'Messages', 'mail', $msgs, 'messages'],
];
?>
<nav class="nav" aria-label="Primary">
  <a class="nav__brand" href="<?= e(url('/feed')) ?>"><span>EMChat</span></a>
  <ul class="nav__list">
    <?php foreach ($items as [$href, $label, $ic, $count, $badge]): ?>
      <li>
        <a class="nav__link<?= $is($href) ?>" href="<?= e(url($href)) ?>"<?= $badge ? ' data-badge="' . $badge . '"' : '' ?>>
          <span class="nav__ico"><?= icon($ic, '', 22) ?></span>
          <span class="nav__label"><?= $label ?></span>
          <?php if ($count > 0): ?><span class="nav__badge"><?= $count > 99 ? '99+' : $count ?></span><?php endif; ?>
        </a>
      </li>
    <?php endforeach; ?>
    <li>
      <a class="nav__link<?= $is('/@' . $user['username']) ?>" href="<?= e(url('@' . $user['username'])) ?>">
        <span class="nav__ico"><?= $view->partial('avatar', ['u' => $user, 'size' => 'xs']) ?></span>
        <span class="nav__label">Profile</span>
      </a>
    </li>
  </ul>
  <div class="nav__foot">
    <a class="nav__link" href="<?= e(url('/settings/profile')) ?>">
      <span class="nav__ico"><?= icon('settings', '', 22) ?></span><span class="nav__label">Settings</span>
    </a>
    <form method="post" action="<?= e(url('/logout')) ?>">
      <?= csrf_field() ?>
      <button class="nav__link" type="submit">
        <span class="nav__ico"><?= icon('logout', '', 22) ?></span><span class="nav__label">Log out</span>
      </button>
    </form>
    <a class="nav__cta btn btn--primary" href="#composer" data-open-composer><?= icon('plus', '', 18) ?> Post</a>
  </div>
</nav>
