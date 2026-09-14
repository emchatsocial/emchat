<?php /** @var array $profile  @var ?array $viewer */ ?>
<div class="cardpage">
  <?= $view->partial('profile_card', ['profile' => $profile, 'viewer' => $viewer, 'variant' => 'full']) ?>
  <a class="btn btn--primary btn--block" href="<?= e(url('@' . $profile['username'])) ?>">View full profile on <?= e(config('app_name')) ?></a>
  <p class="cardpage__foot"><a href="<?= e(url('/')) ?>"><?= e(config('app_name')) ?></a> — <?= e(config('app_tagline')) ?></p>
</div>
