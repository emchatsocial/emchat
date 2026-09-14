<?php /** @var array $profile  @var array $people  @var string $kind  @var ?array $viewer */ ?>
<div class="<?= $viewer ? '' : 'wrap ' ?>connections">
  <div class="page-head">
    <a class="back" href="<?= e(url('@' . $profile['username'])) ?>"><?= icon('arrow-left','',18) ?></a>
    <h1><?= e($profile['display_name']) ?> · <?= ucfirst($kind) ?></h1>
    <span></span>
  </div>
  <?php if (!$people): ?>
    <p class="muted explore__none">No <?= e($kind) ?> yet.</p>
  <?php else: ?>
    <div class="urow-list">
      <?php foreach ($people as $person): ?>
        <?= $view->partial('user_row', ['u' => $person, 'viewer' => $viewer]) ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
