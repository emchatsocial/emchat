<?php
/**
 * Compact person row — search results, follower/following lists, suggestions.
 * @var array  $u        user row (ideally via User::withStats)
 * @var array|null $viewer
 * @var bool   $bio      show one-line bio (default true)
 * @var bool   $connect  show follow/message actions (default true)
 */
$viewer  = $viewer  ?? current_user();
$showBio = $bio ?? true;
$connect = $connect ?? true;
$isSelf  = !empty($u['is_self']) || ($viewer && (int) $viewer['id'] === (int) $u['id']);
$status  = $u['follow_status'] ?? null;
$handle  = $u['username'];
?>
<div class="urow" data-username="<?= e($handle) ?>">
  <a class="urow__main" href="<?= e(url('@' . $handle)) ?>">
    <?= $view->partial('avatar', ['u' => $u, 'size' => 'md']) ?>
    <span class="urow__id">
      <span class="urow__name">
        <?= e($u['display_name']) ?>
        <?php if (!empty($u['is_private'])): ?><?= icon('lock', '', 13) ?><?php endif; ?>
      </span>
      <span class="urow__handle">
        @<?= e($handle) ?><?php if (!empty($u['follows_you']) && !$isSelf): ?><span class="urow__follows">Follows you</span><?php endif; ?>
      </span>
      <?php if ($showBio && !empty($u['bio'])): ?>
        <span class="urow__bio"><?= e($u['bio']) ?></span>
      <?php endif; ?>
    </span>
  </a>

  <?php if ($connect && $viewer && !$isSelf): ?>
    <div class="urow__action" data-follow-widget>
      <?php if ($status === 'accepted'): ?>
        <form method="post" action="<?= e(url('/x/unfollow/' . $handle)) ?>" data-ajax="follow">
          <?= csrf_field() ?><button class="btn btn--ghost btn--sm" data-following>Following</button>
        </form>
      <?php elseif ($status === 'pending'): ?>
        <form method="post" action="<?= e(url('/x/unfollow/' . $handle)) ?>" data-ajax="follow">
          <?= csrf_field() ?><button class="btn btn--ghost btn--sm" data-requested>Requested</button>
        </form>
      <?php else: ?>
        <form method="post" action="<?= e(url('/x/follow/' . $handle)) ?>" data-ajax="follow">
          <?= csrf_field() ?><button class="btn btn--primary btn--sm" data-follow><?= !empty($u['is_private']) ? 'Request' : 'Follow' ?></button>
        </form>
      <?php endif; ?>
    </div>
  <?php elseif ($connect && $isSelf): ?>
    <span class="urow__action chip">You</span>
  <?php elseif ($connect && !$viewer): ?>
    <a class="btn btn--primary btn--sm urow__action" href="<?= e(url('/login')) ?>">Follow</a>
  <?php endif; ?>
</div>
