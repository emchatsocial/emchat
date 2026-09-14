<?php /** @var array $post  @var ?array $parent  @var array $replies  @var ?array $viewer */ ?>
<div class="<?= $viewer ? '' : 'wrap ' ?>thread">
  <div class="page-head">
    <a class="back" href="<?= e($viewer ? url('/feed') : url('/explore')) ?>"><?= icon('arrow-left','',18) ?> Back</a>
    <h1>Post</h1>
  </div>

  <?php if ($parent): ?>
    <div class="thread__parent">
      <?= $view->partial('post', ['post' => $parent, 'viewer' => $viewer]) ?>
      <div class="thread__connector"></div>
    </div>
  <?php endif; ?>

  <?= $view->partial('post', ['post' => $post, 'viewer' => $viewer]) ?>

  <div class="thread__stats">
    <a href="<?= e(url("/p/{$post['id']}/likes")) ?>"><strong><?= (int) $post['like_count'] ?></strong> likes</a>
    <span><strong><?= (int) $post['reply_count'] ?></strong> replies</span>
  </div>

  <?php if ($viewer): ?>
    <div class="thread__reply" id="reply">
      <?= $view->partial('composer', ['user' => $viewer, 'replyTo' => (int) $post['id'], 'placeholder' => 'Post your reply']) ?>
    </div>
  <?php else: ?>
    <p class="thread__signin"><a href="<?= e(url('/login')) ?>">Log in</a> to reply.</p>
  <?php endif; ?>

  <div class="stream thread__replies" data-stream>
    <?php foreach ($replies as $reply): ?>
      <?= $view->partial('post', ['post' => $reply, 'viewer' => $viewer]) ?>
    <?php endforeach; ?>
  </div>
</div>
