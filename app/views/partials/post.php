<?php
/** @var array $post  @var array|null $viewer */
$viewer = $viewer ?? current_user();
$isOwner = $viewer && (int) $viewer['id'] === (int) $post['user_id'];
$permalink = url("/p/{$post['id']}");
$author = ['username' => $post['username'], 'display_name' => $post['display_name'], 'avatar_path' => $post['avatar_path']];
$edited = !empty($post['edited_at']);
$followStatus = $post['author_follow_status'] ?? null;
?>
<article class="post" id="post-<?= (int) $post['id'] ?>" data-post="<?= (int) $post['id'] ?>" data-owner="<?= $isOwner ? '1' : '0' ?>">
  <a class="post__avatar" href="<?= e(url('@' . $post['username'])) ?>">
    <?= $view->partial('avatar', ['u' => $author, 'size' => 'md']) ?>
  </a>
  <div class="post__main">
    <header class="post__head">
      <span class="post__meta">
        <a class="post__author" href="<?= e(url('@' . $post['username'])) ?>"><?= e($post['display_name']) ?></a>
        <span class="post__handle">@<?= e($post['username']) ?></span>
        <span class="post__sep">·</span>
        <a class="post__time" href="<?= e($permalink) ?>"><time datetime="<?= e(date('c', strtotime($post['created_at']))) ?>"><?= e(time_ago($post['created_at'])) ?></time></a>
        <?php if ($edited): ?><span class="post__edited" title="Edited <?= e(date('M j, Y g:i a', strtotime($post['edited_at']))) ?>">· edited</span><?php endif; ?>
        <?php if (!empty($post['is_suggested'])): ?><span class="post__suggested" title="Public post from someone you don't follow, shown because it's popular">· suggested</span><?php endif; ?>
        <?php if ($post['visibility'] !== 'public'): ?>
          <span class="post__vis" title="<?= e(ucfirst($post['visibility'])) ?>"><?= icon($post['visibility'] === 'private' ? 'lock' : 'users', '', 13) ?></span>
        <?php endif; ?>
      </span>

      <?php if ($viewer): ?>
        <details class="post__menu">
          <summary aria-label="Post options"><?= icon('more', '', 18) ?></summary>
          <div class="menu">
            <?php if ($isOwner): ?>
              <button class="menu__item" type="button" data-edit-post><?= icon('pencil') ?> Edit post</button>
              <button class="menu__item" type="button" data-copy="<?= e($permalink) ?>"><?= icon('link') ?> Copy link</button>
              <form method="post" action="<?= e(url("/p/{$post['id']}/delete")) ?>" data-ajax="delete">
                <?= csrf_field() ?>
                <button class="menu__item menu__item--danger" type="submit"><?= icon('trash') ?> Delete post</button>
              </form>
            <?php else: ?>
              <button class="menu__item" type="button" data-copy="<?= e($permalink) ?>"><?= icon('link') ?> Copy link</button>
              <?php if ($followStatus === 'accepted'): ?>
                <form method="post" action="<?= e(url('/x/unfollow/' . $post['username'])) ?>" data-ajax="follow">
                  <?= csrf_field() ?><button class="menu__item" type="submit"><?= icon('user') ?> Unfollow @<?= e($post['username']) ?></button>
                </form>
              <?php else: ?>
                <form method="post" action="<?= e(url('/x/follow/' . $post['username'])) ?>" data-ajax="follow">
                  <?= csrf_field() ?><button class="menu__item" type="submit"><?= icon('plus') ?> Follow @<?= e($post['username']) ?></button>
                </form>
              <?php endif; ?>
              <form method="post" action="<?= e(url('/x/mute/' . $post['username'])) ?>" data-ajax="quiet">
                <?= csrf_field() ?><button class="menu__item" type="submit"><?= icon('bell-off') ?> Mute @<?= e($post['username']) ?></button>
              </form>
              <form method="post" action="<?= e(url('/x/block/' . $post['username'])) ?>" data-ajax="quiet" data-confirm="Block @<?= e($post['username']) ?>?">
                <?= csrf_field() ?><button class="menu__item" type="submit"><?= icon('ban') ?> Block @<?= e($post['username']) ?></button>
              </form>
              <form method="post" action="<?= e(url("/p/{$post['id']}/report")) ?>" data-ajax="report" data-report>
                <?= csrf_field() ?><input type="hidden" name="reason" value="other">
                <button class="menu__item menu__item--danger" type="submit"><?= icon('flag') ?> Report post</button>
              </form>
            <?php endif; ?>
          </div>
        </details>
      <?php endif; ?>
    </header>

    <div class="post__body" data-post-body<?= trim($post['body']) === '' ? ' hidden' : '' ?>><?= $post['body_html'] ?></div>

    <?php if ($isOwner): ?>
      <form class="post__edit" method="post" action="<?= e(url("/p/{$post['id']}/edit")) ?>" data-ajax="edit-post" hidden>
        <?= csrf_field() ?>
        <textarea name="body" rows="3" maxlength="2000" data-autogrow><?= e($post['body']) ?></textarea>
        <div class="post__edit-bar">
          <select name="visibility" aria-label="Visibility">
            <?php foreach (['public' => 'Public', 'followers' => 'Followers only', 'private' => 'Only me'] as $v => $lbl): ?>
              <option value="<?= $v ?>" <?= $post['visibility'] === $v ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn--ghost btn--sm" type="button" data-cancel-edit>Cancel</button>
          <button class="btn btn--primary btn--sm" type="submit">Save</button>
        </div>
      </form>
    <?php endif; ?>

    <?php if (!empty($post['media'])): ?>
      <div class="post__media post__media--<?= count($post['media']) ?>">
        <?php foreach ($post['media'] as $mItem): ?>
          <a href="<?= e(media_url($mItem['path'])) ?>" target="_blank" class="post__media-item">
            <img src="<?= e(media_url($mItem['path'])) ?>" alt="<?= e($mItem['alt']) ?>" loading="lazy"
                 width="<?= (int) $mItem['width'] ?>" height="<?= (int) $mItem['height'] ?>">
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <footer class="post__actions">
      <a class="post__action" href="<?= e($permalink) ?>#reply" aria-label="Reply">
        <?= icon('comment') ?><span class="cnt" data-replies><?= (int) $post['reply_count'] ?: '' ?></span>
      </a>
      <?php if ($viewer): ?>
        <form method="post" action="<?= e(url("/x/like/{$post['id']}")) ?>" data-ajax="like" class="post__like <?= $post['liked_by_viewer'] ? 'is-liked' : '' ?>">
          <?= csrf_field() ?>
          <button class="post__action" aria-pressed="<?= $post['liked_by_viewer'] ? 'true' : 'false' ?>" aria-label="Like">
            <span class="post__heart"><?= icon('heart', 'ic--o') ?><?= icon('heart-fill', 'ic--f') ?></span>
            <span class="cnt" data-like-count><?= (int) $post['like_count'] ?: '' ?></span>
          </button>
        </form>
      <?php else: ?>
        <a class="post__action" href="<?= e(url('/login')) ?>" aria-label="Like"><?= icon('heart') ?><span class="cnt"><?= (int) $post['like_count'] ?: '' ?></span></a>
      <?php endif; ?>
      <button class="post__action" type="button" data-copy="<?= e($permalink) ?>" aria-label="Copy link"><?= icon('link') ?></button>
    </footer>
  </div>
</article>
