<?php
/** @var array $people  @var int $total  @var int $page  @var int $per_page  @var string $query  @var array $admin */
$pages = max(1, (int) ceil($total / $per_page));
?>
<div class="page-head"><h1>Users</h1></div>

<form class="admin-search" method="get" action="<?= e(url('/admin/users')) ?>">
  <input type="search" name="q" value="<?= e($query) ?>" placeholder="Search by handle, name, or email">
  <button class="btn btn--primary btn--sm" type="submit"><?= icon('search', '', 15) ?> Search</button>
  <?php if ($query !== ''): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/users')) ?>">Clear</a><?php endif; ?>
</form>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Person</th>
        <th>Email</th>
        <th>Role</th>
        <th>Posts</th>
        <th>Joined</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($people as $p): $isSelf = (int) $p['id'] === (int) $admin['id']; $suspended = $p['suspended_at'] !== null; ?>
        <tr>
          <td>
            <a class="admin-userlink" href="<?= e(url('@' . $p['username'])) ?>" target="_blank" rel="noopener">
              <?= $view->partial('avatar', ['u' => $p, 'size' => 'sm']) ?>
              <span><strong><?= e($p['display_name']) ?></strong><small>@<?= e($p['username']) ?></small></span>
            </a>
          </td>
          <td class="admin-table__mono"><?= e($p['email']) ?></td>
          <td>
            <?php if ($p['role'] === 'admin'): ?>
              <span class="admin-pill admin-pill--admin"><?= icon('crown', '', 12) ?> Admin</span>
            <?php else: ?>
              <span class="admin-pill">User</span>
            <?php endif; ?>
          </td>
          <td><?= (int) $p['posts_count'] ?></td>
          <td><?= e(date('M j, Y', strtotime($p['created_at']))) ?></td>
          <td>
            <?php if ($suspended): ?><span class="admin-pill admin-pill--danger">Suspended</span>
            <?php else: ?><span class="admin-pill admin-pill--ok">Active</span><?php endif; ?>
          </td>
          <td class="admin-table__actions">
            <?php if (!$isSelf): ?>
              <form method="post" action="<?= e(url('/admin/users/' . $p['id'] . '/role')) ?>" data-ajax="admin-action">
                <?= csrf_field() ?>
                <input type="hidden" name="role" value="<?= $p['role'] === 'admin' ? 'user' : 'admin' ?>">
                <button class="btn btn--ghost btn--sm" type="submit">
                  <?= $p['role'] === 'admin' ? 'Remove admin' : 'Make admin' ?>
                </button>
              </form>
              <?php if ($suspended): ?>
                <form method="post" action="<?= e(url('/admin/users/' . $p['id'] . '/unsuspend')) ?>" data-ajax="admin-action">
                  <?= csrf_field() ?>
                  <button class="btn btn--ghost btn--sm" type="submit">Reinstate</button>
                </form>
              <?php else: ?>
                <form method="post" action="<?= e(url('/admin/users/' . $p['id'] . '/suspend')) ?>" data-ajax="admin-action" data-confirm="Suspend @<?= e($p['username']) ?>? They'll be signed out immediately.">
                  <?= csrf_field() ?>
                  <button class="btn btn--ghost btn--danger btn--sm" type="submit">Suspend</button>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <span class="muted">You</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$people): ?>
        <tr><td colspan="7" class="admin-table__empty">No users match that search.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($pages > 1): ?>
  <nav class="admin-pager">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a class="<?= $i === $page ? 'is-active' : '' ?>" href="<?= e(url('/admin/users?page=' . $i . ($query !== '' ? '&q=' . rawurlencode($query) : ''))) ?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
<?php endif; ?>
