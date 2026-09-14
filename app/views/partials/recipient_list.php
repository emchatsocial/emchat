<?php /** @var array $people */ ?>
<?php if (!$people): ?>
  <p class="muted explore__none">No people found. Follow someone first, or search by handle.</p>
<?php else: ?>
  <div class="urow-list">
    <?php foreach ($people as $p): ?>
      <a class="urow urow--pick" href="<?= e(url('/messages/new/' . $p['username'])) ?>">
        <span class="urow__main">
          <?= $view->partial('avatar', ['u' => $p, 'size' => 'md']) ?>
          <span class="urow__id">
            <span class="urow__name"><?= e($p['display_name']) ?></span>
            <span class="urow__handle">@<?= e($p['username']) ?></span>
          </span>
        </span>
        <span class="urow__action chip chip--soft">Message</span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
