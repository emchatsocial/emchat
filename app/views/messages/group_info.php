<?php
/** @var array $threads  @var array $group  @var array $members  @var array $addable  @var bool $is_admin  @var int $cid  @var mixed $active */
$me = (int) current_user()['id'];
?>
<div class="messenger">
  <div class="messenger__list">
    <?= $view->partial('thread_list', ['threads' => $threads, 'active' => $active]) ?>
  </div>
  <div class="messenger__panel">
    <header class="messenger__head">
      <a class="back back--inline" href="<?= e(url('/messages/' . $cid)) ?>"><?= icon('arrow-left', '', 18) ?></a>
      <strong>Group info</strong>
    </header>

    <div class="ginfo">
      <div class="ginfo__id">
        <?php if (!empty($group['photo_path'])): ?>
          <img class="ginfo__photo" src="<?= e(media_url($group['photo_path'])) ?>" alt="" width="120" height="120">
        <?php else: ?>
          <span class="ginfo__photo ginfo__photo--fallback" aria-hidden="true"><?= icon('users', '', 34) ?></span>
        <?php endif; ?>
        <?php if ($is_admin): ?>
          <form method="post" action="<?= e(url('/messages/' . $cid . '/photo')) ?>" enctype="multipart/form-data" data-group-action>
            <?= csrf_field() ?>
            <label class="btn btn--ghost btn--sm">
              <?= icon('camera', '', 15) ?> Change photo
              <input type="file" name="photo" accept="image/*" hidden onchange="this.form.submit()">
            </label>
          </form>
        <?php endif; ?>
      </div>

      <?php if ($is_admin): ?>
        <form class="ginfo__rename" method="post" action="<?= e(url('/messages/' . $cid . '/rename')) ?>" data-group-action>
          <?= csrf_field() ?>
          <input type="text" name="title" value="<?= e($group['title']) ?>" maxlength="80" aria-label="Group name">
          <button class="btn btn--ghost btn--sm" type="submit">Save</button>
        </form>
      <?php else: ?>
        <h2 class="ginfo__title"><?= e($group['title'] ?: 'Group') ?></h2>
      <?php endif; ?>

      <p class="ginfo__meta"><?= (int) $group['member_count'] ?> members</p>

      <?php if ($is_admin && $addable): ?>
        <details class="ginfo__add">
          <summary><?= icon('user-plus', '', 16) ?> Add people</summary>
          <form method="post" action="<?= e(url('/messages/' . $cid . '/members')) ?>" data-group-action>
            <?= csrf_field() ?>
            <div class="ginfo__addlist">
              <?php foreach ($addable as $p): ?>
                <label class="pickrow">
                  <span class="urow__main">
                    <?= $view->partial('avatar', ['u' => $p, 'size' => 'sm']) ?>
                    <span class="urow__id">
                      <span class="urow__name"><?= e($p['display_name']) ?></span>
                      <span class="urow__handle">@<?= e($p['username']) ?></span>
                    </span>
                  </span>
                  <input type="checkbox" name="members[]" value="<?= (int) $p['id'] ?>">
                  <span class="pickrow__box" aria-hidden="true"><?= icon('check', '', 14) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <button class="btn btn--primary btn--sm" type="submit">Add selected</button>
          </form>
        </details>
      <?php endif; ?>

      <div class="ginfo__members">
        <?php foreach ($members as $m): $mid = (int) $m['id']; $isAdminRow = $m['role'] === 'admin'; ?>
          <div class="mrow">
            <a class="mrow__who" href="<?= e(url('@' . $m['username'])) ?>">
              <?= $view->partial('avatar', ['u' => $m, 'size' => 'md']) ?>
              <span class="urow__id">
                <span class="urow__name"><?= e($m['display_name']) ?><?= $mid === $me ? ' <span class="mrow__you">(you)</span>' : '' ?></span>
                <span class="urow__handle">@<?= e($m['username']) ?></span>
              </span>
            </a>
            <div class="mrow__side">
              <?php if ($isAdminRow): ?><span class="mrow__badge"><?= icon('crown', '', 12) ?> Admin</span><?php endif; ?>
              <?php if ($is_admin && $mid !== $me): ?>
                <details class="mrow__menu">
                  <summary aria-label="Member options"><?= icon('more', '', 16) ?></summary>
                  <div class="menu">
                    <form method="post" action="<?= e(url('/messages/' . $cid . '/members/' . $mid . '/role')) ?>" data-group-action>
                      <?= csrf_field() ?>
                      <input type="hidden" name="role" value="<?= $isAdminRow ? 'member' : 'admin' ?>">
                      <button class="menu__item" type="submit"><?= icon($isAdminRow ? 'user-minus' : 'crown') ?> <?= $isAdminRow ? 'Remove as admin' : 'Make admin' ?></button>
                    </form>
                    <form method="post" action="<?= e(url('/messages/' . $cid . '/members/' . $mid . '/remove')) ?>"
                          data-group-action data-confirm="Remove <?= e($m['display_name']) ?> from the group?">
                      <?= csrf_field() ?>
                      <button class="menu__item menu__item--danger" type="submit"><?= icon('user-minus') ?> Remove from group</button>
                    </form>
                  </div>
                </details>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <form class="ginfo__leave" method="post" action="<?= e(url('/messages/' . $cid . '/leave')) ?>"
            data-group-action data-confirm="Leave this group?">
        <?= csrf_field() ?>
        <button class="btn btn--ghost btn--danger" type="submit"><?= icon('logout', '', 16) ?> Leave group</button>
      </form>
    </div>
  </div>
</div>
