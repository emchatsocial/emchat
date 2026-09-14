<?php /** @var array $threads  @var array $people  @var string $query  @var mixed $active */ ?>
<div class="messenger">
  <div class="messenger__list">
    <?= $view->partial('thread_list', ['threads' => $threads, 'active' => $active]) ?>
  </div>
  <div class="messenger__panel">
    <header class="messenger__head">
      <a class="back back--inline" href="<?= e(url('/messages')) ?>"><?= icon('arrow-left', '', 18) ?></a>
      <strong>New message</strong>
    </header>
    <div class="newmsg">
      <a class="urow urow--pick newmsg__group" href="<?= e(url('/messages/new/group')) ?>">
        <span class="urow__main">
          <span class="avatar avatar--md avatar--fallback" aria-hidden="true"><?= icon('users', '', 18) ?></span>
          <span class="urow__id">
            <span class="urow__name">New group</span>
            <span class="urow__handle">Chat with several people</span>
          </span>
        </span>
        <span class="urow__action"><?= icon('chevron-right', '', 16) ?></span>
      </a>
      <form class="searchbar newmsg__search" method="get" action="<?= e(url('/messages/new')) ?>" role="search" data-recipient-search>
        <span class="searchbar__ico" aria-hidden="true"><?= icon('search', '', 15) ?></span>
        <input type="search" name="q" value="<?= e($query) ?>" placeholder="Search people by name or @handle" aria-label="Search people" autofocus autocomplete="off">
      </form>
      <div class="newmsg__results" data-recipient-results>
        <?= $view->partial('recipient_list', ['people' => $people]) ?>
      </div>
    </div>
  </div>
</div>
