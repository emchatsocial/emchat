<?php /** @var array $profile */ ?>
<section class="wrap errorpage">
  <p class="errorpage__code"><?= icon('ban', '', 48) ?></p>
  <h1>You can't view @<?= e($profile['username']) ?>'s profile</h1>
  <p>@<?= e($profile['username']) ?> has blocked you, so you can't see their posts, follow them or send them messages.</p>
  <p><a class="btn btn--primary" href="<?= e(url('/feed')) ?>">Back to your feed</a></p>
</section>
