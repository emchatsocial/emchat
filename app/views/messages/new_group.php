<?php /** @var array $threads  @var array $people  @var mixed $active */ ?>
<div class="messenger">
  <div class="messenger__list">
    <?= $view->partial('thread_list', ['threads' => $threads, 'active' => $active]) ?>
  </div>
  <div class="messenger__panel">
    <header class="messenger__head">
      <a class="back back--inline" href="<?= e(url('/messages/new')) ?>"><?= icon('arrow-left', '', 18) ?></a>
      <strong>New group</strong>
    </header>

    <form class="newgroup" method="post" action="<?= e(url('/messages/group')) ?>" data-group-form>
      <?= csrf_field() ?>
      <div class="newgroup__name">
        <label class="sr-only" for="group-title">Group name</label>
        <input id="group-title" type="text" name="title" maxlength="80" placeholder="Group name" autocomplete="off" autofocus>
      </div>

      <div class="newgroup__label">
        <span>Add people</span>
        <span class="muted" data-group-count>0 selected</span>
      </div>

      <?php if (!$people): ?>
        <p class="muted newgroup__none">You need to follow people (or be followed) before you can add them to a group.</p>
      <?php else: ?>
        <div class="newgroup__people">
          <?php foreach ($people as $p): ?>
            <label class="pickrow">
              <span class="urow__main">
                <?= $view->partial('avatar', ['u' => $p, 'size' => 'md']) ?>
                <span class="urow__id">
                  <span class="urow__name"><?= e($p['display_name']) ?></span>
                  <span class="urow__handle">@<?= e($p['username']) ?></span>
                </span>
              </span>
              <input type="checkbox" name="members[]" value="<?= (int) $p['id'] ?>" data-group-check>
              <span class="pickrow__box" aria-hidden="true"><?= icon('check', '', 14) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="newgroup__foot">
        <button class="btn btn--primary" type="submit" data-group-submit disabled>Create group</button>
      </div>
    </form>
  </div>
</div>
