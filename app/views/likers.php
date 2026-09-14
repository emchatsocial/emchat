<?php /** @var array $post  @var array $people  @var ?array $viewer */ ?>
<div class="<?= $viewer ? '' : 'wrap ' ?>connections">
  <div class="page-head">
    <a class="back" href="<?= e(url("/p/{$post['id']}")) ?>"><?= icon('arrow-left','',18) ?></a>
    <h1>Liked by</h1>
    <span></span>
  </div>
  <?php if (!$people): ?>
    <p class="muted explore__none">No likes yet.</p>
  <?php else: ?>
    <div class="urow-list">
      <?php foreach ($people as $person): ?>
        <?= $view->partial('user_row', [
            'u'      => \App\Models\User::withStats($person, $viewer ? (int) $viewer['id'] : null),
            'viewer' => $viewer,
        ]) ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
