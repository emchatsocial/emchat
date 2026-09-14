<?php /** @var array $profile  @var array $posts  @var string $tab  @var bool $can_view  @var ?array $viewer */ ?>
<div class="<?= $viewer ? '' : 'wrap ' ?>profile-page">
  <?= $view->partial('profile_card', ['profile' => $profile, 'viewer' => $viewer, 'variant' => 'full']) ?>

  <?php if ($viewer && !$profile['is_self']): ?>
    <details class="profile-more">
      <summary>More options</summary>
      <div class="menu">
        <?php if (empty($profile['blocked'])): ?>
          <form method="post" action="<?= e(url('/x/mute/' . $profile['username'])) ?>"><?= csrf_field() ?><button class="menu__item">Mute @<?= e($profile['username']) ?></button></form>
          <form method="post" action="<?= e(url('/x/block/' . $profile['username'])) ?>" onsubmit="return confirm('Block @<?= e($profile['username']) ?>?')"><?= csrf_field() ?><button class="menu__item menu__item--danger">Block @<?= e($profile['username']) ?></button></form>
        <?php else: ?>
          <form method="post" action="<?= e(url('/x/unblock/' . $profile['username'])) ?>"><?= csrf_field() ?><button class="menu__item">Unblock @<?= e($profile['username']) ?></button></form>
        <?php endif; ?>
      </div>
    </details>
  <?php endif; ?>

  <?php if (!$can_view): ?>
    <div class="empty empty--locked">
      <div class="empty__icon"><?= icon('lock','',34) ?></div>
      <h2>This account is private</h2>
      <p>Follow @<?= e($profile['username']) ?> to see their posts.</p>
    </div>
  <?php else: ?>
    <nav class="tabs">
      <a class="tab <?= $tab === 'posts' ? 'is-active' : '' ?>" href="<?= e(url('@' . $profile['username'])) ?>">Posts</a>
      <a class="tab <?= $tab === 'replies' ? 'is-active' : '' ?>" href="<?= e(url('@' . $profile['username'] . '?tab=replies')) ?>">Replies</a>
      <a class="tab <?= $tab === 'media' ? 'is-active' : '' ?>" href="<?= e(url('@' . $profile['username'] . '?tab=media')) ?>">Media</a>
    </nav>

    <?php if (!$posts): ?>
      <p class="muted profile-page__empty">Nothing to show here yet.</p>
    <?php else: ?>
      <div class="stream">
        <?php foreach ($posts as $post): ?>
          <?= $view->partial('post', ['post' => $post, 'viewer' => $viewer]) ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
