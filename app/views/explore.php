<?php /** @var string $query  @var array $people  @var array $featured */
$user = current_user(); ?>
<div class="<?= $user ? '' : 'wrap ' ?>explore">
  <div class="page-head">
    <h1><?= $query !== '' ? 'Search' : 'Find people' ?></h1>
  </div>

  <form class="searchbar" method="get" action="<?= e(url('/explore')) ?>" role="search">
    <span class="searchbar__ico" aria-hidden="true"><?= icon('search','',15) ?></span>
    <input type="search" name="q" value="<?= e($query) ?>" placeholder="Search people by name or handle" aria-label="Search" autofocus>
    <button class="btn btn--primary btn--sm">Search</button>
  </form>

  <section class="explore__section">
    <?php if ($query === ''): ?>
      <p class="muted explore__none">Search for someone by name or @handle to see their profile and follow them.</p>
    <?php elseif (!$people): ?>
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

  <?php if (!empty($featured)): ?>
    <section class="explore__section">
      <h2 class="explore__label">Latest from EMChat Media</h2>
      <div class="stream">
        <?php foreach ($featured as $post): ?>
          <?= $view->partial('post', ['post' => $post, 'viewer' => null]) ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!$user): ?>
    <div class="cta-band cta-band--inset">
      <h2>Like what you see?</h2>
      <a class="btn btn--primary btn--lg" href="<?= e(url('/login')) ?>">Join EMChat Media</a>
    </div>
  <?php endif; ?>
</div>
