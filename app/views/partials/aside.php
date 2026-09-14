<?php
/** @var array $user */
$suggested = \App\App::db()->all(
    "SELECT u.* FROM users u
     WHERE u.id <> ? AND u.discoverable = 1 AND u.is_private = 0
       AND u.id NOT IN (SELECT followee_id FROM follows WHERE follower_id = ?)
       AND u.id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id = ?)
       AND u.id NOT IN (SELECT blocker_id FROM blocks WHERE blocked_id = ?)
     ORDER BY (SELECT COUNT(*) FROM follows f WHERE f.followee_id = u.id) DESC, u.id DESC LIMIT 4",
    [$user['id'], $user['id'], $user['id'], $user['id']]
);
?>
<form class="searchbar searchbar--aside" method="get" action="<?= e(url('/explore')) ?>" role="search">
  <span class="searchbar__ico" aria-hidden="true"><?= icon('search', '', 15) ?></span>
  <input type="search" name="q" placeholder="Search" aria-label="Search people & posts">
</form>

<?php if ($suggested): ?>
<section class="panel panel--flush">
  <h2 class="panel__title">Who to follow</h2>
  <div class="urow-list urow-list--tight">
    <?php foreach ($suggested as $s): ?>
      <?= $view->partial('user_row', [
          'u'      => \App\Models\User::withStats($s, (int) $user['id']),
          'viewer' => $user,
          'bio'    => false,
      ]) ?>
    <?php endforeach; ?>
  </div>
  <a class="panel__more" href="<?= e(url('/explore')) ?>">Show more</a>
</section>
<?php endif; ?>

<section class="panel panel--privacy">
  <h2 class="panel__title"><?= icon('shield') ?> Privacy first</h2>
  <p>No ad trackers. No behavioural profiling. Your posts are visible only to who you choose.</p>
  <a href="<?= e(url('/privacy')) ?>">How EMChat handles data</a>
</section>

<footer class="aside__foot">
  <a href="<?= e(url('/about')) ?>">About</a> ·
  <a href="<?= e(url('/privacy')) ?>">Privacy</a> ·
  <a href="<?= e(url('/terms')) ?>">Terms</a> ·
  <a href="<?= e(url('/transparency')) ?>">Transparency</a>
  <p>&copy; <?= date('Y') ?> <?= e(config('app_name')) ?></p>
</footer>
