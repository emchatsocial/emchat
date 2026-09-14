<?php /** @var string $query  @var string $tag  @var array $people  @var array $posts */
$user = current_user(); ?>
<div class="<?= $user ? '' : 'wrap ' ?>explore">
  <div class="page-head">
    <h1><?= $tag !== '' ? '#' . e($tag) : ($query !== '' ? 'Search' : 'Explore') ?></h1>
  </div>

  <form class="searchbar" method="get" action="<?= e(url('/explore')) ?>" role="search">
    <span class="searchbar__ico" aria-hidden="true"><?= icon('search','',15) ?></span>
    <input type="search" name="q" value="<?= e($query) ?>" placeholder="Search people and public posts" aria-label="Search" autofocus>
    <button class="btn btn--primary btn--sm">Search</button>
  </form>

  <?php if ($query !== '' || $people): ?>
    <section class="explore__section">
      <h2 class="explore__label">People</h2>
      <?php if (!$people): ?>
        <p class="muted explore__none">No people match “<?= e($query) ?>”.</p>
      <?php else: ?>
        <div class="urow-list">
          <?php foreach ($people as $person): ?>
            <?= $view->partial('user_row', [
                'u'      => \App\Models\User::withStats($person, $user ? (int) $user['id'] : null),
                'viewer' => $user,
            ]) ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <section class="explore__section">
    <h2 class="explore__label"><?= $tag !== '' ? 'Posts tagged #' . e($tag) : 'Latest public posts' ?></h2>
    <?php if (!$posts): ?>
      <p class="muted explore__none">Nothing here yet.</p>
    <?php else: ?>
      <div class="stream">
        <?php foreach ($posts as $post): ?>
          <?= $view->partial('post', ['post' => $post, 'viewer' => $user]) ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <?php if (!$user): ?>
    <div class="cta-band cta-band--inset">
      <h2>Like what you see?</h2>
      <a class="btn btn--primary btn--lg" href="<?= e(url('/login')) ?>">Join EMChat Media</a>
    </div>
  <?php endif; ?>
</div>
