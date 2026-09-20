<section class="wrap prose">
  <h1>Updates</h1>
  <p class="prose__lede">News, feature releases, and milestones from EMChat, newest first.</p>

  <?php foreach ($entries as $entry): ?>
  <h2><?= e($entry['headline']) ?></h2>
  <p class="muted"><time datetime="<?= e($entry['date']) ?>"><?= e(date('F j, Y', strtotime($entry['date']))) ?></time></p>
  <p><?= e($entry['body']) ?></p>
  <script type="application/ld+json"><?= str_replace('</', '<\/', $entry['jsonld']) /* pre-encoded JSON; escape </ so it can never close this tag early */ ?></script>
  <?php endforeach; ?>
</section>
