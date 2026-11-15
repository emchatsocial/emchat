<?php /** @var array $articles */ ?>
<section class="wrap prose">
  <h1>Blog</h1>
  <p class="prose__lede">Practical guides on online privacy, photo safety and using social media on your own terms.</p>

  <?php if (!$articles): ?>
    <p class="muted">No articles yet. Check back soon.</p>
  <?php endif; ?>

  <?php foreach ($articles as $a): ?>
    <article>
      <h2><a href="<?= e(url('/blog/' . $a['slug'])) ?>"><?= e($a['title']) ?></a></h2>
      <p class="muted"><time datetime="<?= e($a['date']) ?>"><?= e(date('F j, Y', strtotime($a['date']))) ?></time></p>
      <p><?= e($a['description']) ?></p>
      <p><a href="<?= e(url('/blog/' . $a['slug'])) ?>">Read the article &rarr;</a></p>
    </article>
  <?php endforeach; ?>
</section>
