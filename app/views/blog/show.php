<?php /** @var array $article  @var array $others */ ?>
<article class="wrap prose">
  <p class="muted"><a href="<?= e(url('/blog')) ?>">&larr; All articles</a></p>
  <h1><?= e($article['title']) ?></h1>
  <p class="muted">
    <time datetime="<?= e($article['date']) ?>"><?= e(date('F j, Y', strtotime($article['date']))) ?></time>
    <?php if ($article['updated'] !== $article['date']): ?>
      &middot; Updated <time datetime="<?= e($article['updated']) ?>"><?= e(date('F j, Y', strtotime($article['updated']))) ?></time>
    <?php endif; ?>
    &middot; EMChat Media
  </p>

  <?= $article['body'] /* trusted HTML written by the site owner in app/content/blog */ ?>

  <h2>Try EMChat Media</h2>
  <p>EMChat Media is a social network with no ad trackers and a feed that only shows the people you follow, newest first. It's free, and you sign in with an email link, no password needed.</p>
  <p><a class="btn btn--primary" href="<?= e(url('/login')) ?>">Join EMChat Media</a></p>

  <?php if ($others): ?>
    <h2>More from the blog</h2>
    <ul>
      <?php foreach ($others as $o): ?>
        <li><a href="<?= e(url('/blog/' . $o['slug'])) ?>"><?= e($o['title']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</article>
