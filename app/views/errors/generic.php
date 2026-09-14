<?php /** @var int $status  @var string $message */ ?>
<section class="wrap errorpage">
  <p class="errorpage__code"><?= (int) $status ?></p>
  <h1><?= e($message) ?></h1>
  <p><a class="btn btn--primary" href="<?= e(url('/')) ?>">Back to home</a></p>
</section>
