<?php
/** @var array $u  @var string $size */
$size = $size ?? 'md';
$path = $u['avatar_path'] ?? null;
$name = $u['display_name'] ?? ($u['username'] ?? '?');
?>
<?php if ($path): ?>
<img class="avatar avatar--<?= e($size) ?>" src="<?= e(media_url($path)) ?>" alt="" loading="lazy" width="96" height="96">
<span class="avatar avatar--<?= e($size) ?> avatar--fallback" aria-hidden="true" hidden><?= e(initials($name)) ?></span>
<?php else: ?>
<span class="avatar avatar--<?= e($size) ?> avatar--fallback" aria-hidden="true"><?= e(initials($name)) ?></span>
<?php endif; ?>
