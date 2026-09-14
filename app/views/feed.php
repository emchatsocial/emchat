<?php /** @var array $posts  @var ?int $next  @var array $suggestions  @var bool $empty_hint */
$user = current_user(); ?>
<div class="page-head">
  <h1>Home</h1>
</div>

<div class="composer-inline" id="composer-inline">
  <?= $view->partial('composer', ['user' => $user]) ?>
</div>

<?php if ($empty_hint): ?>
  <div class="empty">
    <h2>Your timeline is quiet</h2>
    <p>Follow a few people to fill your feed. We'll also mix in a few popular public posts so it's never empty.</p>
    <?php if ($suggestions): ?>
      <div class="urow-list empty__suggestions">
        <?php foreach ($suggestions as $s): ?>
          <?= $view->partial('user_row', ['u' => \App\Models\User::withStats($s, (int) $user['id']), 'viewer' => $user]) ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <a class="btn btn--primary" href="<?= e(url('/explore')) ?>">Explore EMChat</a>
  </div>
<?php endif; ?>

<div class="stream" data-stream>
  <?php foreach ($posts as $post): ?>
    <?= $view->partial('post', ['post' => $post, 'viewer' => $user]) ?>
  <?php endforeach; ?>
</div>

<?php if ($next): ?>
  <button class="btn btn--ghost btn--block load-more" data-load-more data-next="<?= (int) $next ?>" data-url="<?= e(url('/feed')) ?>">
    Load older posts
  </button>
<?php endif; ?>
