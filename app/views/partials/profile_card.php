<?php
/**
 * Full profile hero card (profile page + shareable /@user/card).
 * For compact people lists use partials/user_row instead.
 *
 * @var array  $profile   user row + stats (from User::withStats)
 * @var array|null $viewer
 * @var string $variant   'full' (default) — 'compact'/'mini' fall back to user_row
 */
$viewer  = $viewer  ?? current_user();
$variant = $variant ?? 'full';

if ($variant !== 'full') {
    echo $view->partial('user_row', ['u' => $profile, 'viewer' => $viewer, 'bio' => $variant !== 'mini']);
    return;
}

$p = $profile;
$isSelf = !empty($p['is_self']) || ($viewer && (int) $viewer['id'] === (int) $p['id']);
$status = $p['follow_status'] ?? null;
$handleUrl = url('@' . $p['username']);
?>
<article class="pcard pcard--full" data-username="<?= e($p['username']) ?>">
  <div class="pcard__body">
    <div class="pcard__toprow">
      <a class="pcard__avatar" href="<?= e($handleUrl) ?>" aria-label="<?= e($p['display_name']) ?>'s profile">
        <?= $view->partial('avatar', ['u' => $p, 'size' => 'xl']) ?>
      </a>
      <div class="pcard__actions" data-follow-widget>
        <?php if ($isSelf): ?>
          <a class="btn btn--ghost btn--sm" href="<?= e(url('/settings/profile')) ?>">Edit profile</a>
        <?php elseif ($viewer): ?>
          <a class="btn btn--icon" href="<?= e(url('/messages/new/' . $p['username'])) ?>" title="Message" aria-label="Message @<?= e($p['username']) ?>"><?= icon('mail', '', 18) ?></a>
          <?php if ($status === 'accepted'): ?>
            <form method="post" action="<?= e(url('/x/unfollow/' . $p['username'])) ?>" data-ajax="follow">
              <?= csrf_field() ?><button class="btn btn--ghost btn--sm" data-following>Following</button>
            </form>
          <?php elseif ($status === 'pending'): ?>
            <form method="post" action="<?= e(url('/x/unfollow/' . $p['username'])) ?>" data-ajax="follow">
              <?= csrf_field() ?><button class="btn btn--ghost btn--sm" data-requested>Requested</button>
            </form>
          <?php else: ?>
            <form method="post" action="<?= e(url('/x/follow/' . $p['username'])) ?>" data-ajax="follow">
              <?= csrf_field() ?><button class="btn btn--primary btn--sm" data-follow><?= $p['is_private'] ? 'Request' : 'Follow' ?></button>
            </form>
          <?php endif; ?>
        <?php else: ?>
          <a class="btn btn--primary btn--sm" href="<?= e(url('/login')) ?>">Follow</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="pcard__names">
      <h1 class="pcard__name">
        <?= e($p['display_name']) ?>
        <?php if (!empty($p['is_private'])): ?><?= icon('lock', '', 15) ?><?php endif; ?>
        <?php if (($p['role'] ?? 'user') === 'admin'): ?>
          <span class="pcard__badge" title="Administrator"><?= icon('shield', '', 12) ?> Administrator</span>
        <?php endif; ?>
      </h1>
      <span class="pcard__handle">@<?= e($p['username']) ?>
        <?php if (!empty($p['follows_you']) && !$isSelf): ?><span class="chip chip--soft">Follows you</span><?php endif; ?>
      </span>
    </div>

    <?php if (!empty($p['bio'])): ?><p class="pcard__bio"><?= rich_text($p['bio']) ?></p><?php endif; ?>

    <ul class="pcard__meta">
      <?php if (!empty($p['location'])): ?><li><?= icon('compass','',15) ?> <?= e($p['location']) ?></li><?php endif; ?>
      <?php if (!empty($p['website'])): ?>
        <li><?= icon('link','',15) ?> <a href="<?= e($p['website']) ?>" rel="nofollow noopener" target="_blank"><?= e(preg_replace('~^https?://(www\.)?~', '', $p['website'])) ?></a></li>
      <?php endif; ?>
      <?php if (!empty($p['created_at'])): ?><li><?= icon('sparkle','',15) ?> Joined <?= e(date('M Y', strtotime($p['created_at']))) ?></li><?php endif; ?>
    </ul>

    <div class="pcard__stats">
      <a href="<?= e($handleUrl) ?>/following"><strong><?= number_format((int) ($p['following_count'] ?? 0)) ?></strong> Following</a>
      <a href="<?= e($handleUrl) ?>/followers"><strong data-followers><?= number_format((int) ($p['followers_count'] ?? 0)) ?></strong> Followers</a>
      <span><strong><?= number_format((int) ($p['posts_count'] ?? 0)) ?></strong> Posts</span>
    </div>
  </div>
</article>
