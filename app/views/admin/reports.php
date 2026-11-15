<?php
/** @var array $reports */
use App\Models\Report as ReportModel;
?>
<div class="page-head"><h1>Reports</h1></div>

<?php if (!$reports): ?>
  <div class="empty"><h2>Nothing to review</h2><p>No open reports right now.</p></div>
<?php else: ?>
  <div class="admin-reports">
    <?php foreach ($reports as $r): $subj = $r['subject_preview']; ?>
      <article class="admin-report">
        <header class="admin-report__head">
          <span class="admin-pill"><?= icon('flag', '', 12) ?> <?= e(ucfirst($r['subject_type'])) ?></span>
          <span class="admin-report__reason"><?= e(ReportModel::REASONS[$r['reason']] ?? 'Other') ?></span>
          <time class="muted"><?= e(time_ago($r['created_at'])) ?></time>
        </header>

        <?php if ($r['note'] !== ''): ?><p class="admin-report__note">"<?= e($r['note']) ?>"</p><?php endif; ?>

        <div class="admin-report__subject">
          <?php if (!$subj): ?>
            <p class="muted">This <?= e($r['subject_type']) ?> no longer exists.</p>
          <?php elseif ($r['subject_type'] === 'post'): ?>
            <p><strong>@<?= e($subj['username']) ?>:</strong> <?= e(mb_strimwidth($subj['body'], 0, 200, '…')) ?></p>
            <a href="<?= e(url('/p/' . $subj['id'])) ?>" target="_blank" rel="noopener">View post →</a>
          <?php elseif ($r['subject_type'] === 'user'): ?>
            <p><strong>@<?= e($subj['username']) ?></strong> — <?= e($subj['display_name']) ?></p>
            <a href="<?= e(url('@' . $subj['username'])) ?>" target="_blank" rel="noopener">View profile →</a>
          <?php elseif ($r['subject_type'] === 'message' && \App\Models\E2E::isEnvelope((string) $subj['body'])): ?>
            <p><strong>@<?= e($subj['username']) ?>:</strong> <em>End-to-end encrypted message.</em></p>
            <?php if (!empty($r['evidence'])): ?>
              <p class="muted">Text shared by the reporter (not verifiable by the server):</p>
              <p>"<?= e(mb_strimwidth((string) $r['evidence'], 0, 600, '…')) ?>"</p>
            <?php else: ?>
              <p class="muted">The reporter's browser did not share the text.</p>
            <?php endif; ?>
          <?php elseif ($r['subject_type'] === 'message'): ?>
            <p><strong>@<?= e($subj['username']) ?>:</strong> <?= e(mb_strimwidth((string) $subj['body'], 0, 200, '…')) ?></p>
          <?php endif; ?>
        </div>

        <footer class="admin-report__foot">
          <span class="muted">Reported by @<?= e($r['reporter_username']) ?></span>
          <div class="admin-report__actions">
            <form method="post" action="<?= e(url('/admin/reports/' . $r['id'] . '/dismiss')) ?>" data-ajax="admin-action">
              <?= csrf_field() ?>
              <button class="btn btn--ghost btn--sm" type="submit"><?= icon('x', '', 14) ?> Dismiss</button>
            </form>
            <?php if ($subj): ?>
              <form method="post" action="<?= e(url('/admin/reports/' . $r['id'] . '/action')) ?>" data-ajax="admin-action"
                    data-confirm="<?= $r['subject_type'] === 'user' ? 'Suspend this account' : 'Remove this ' . e($r['subject_type']) ?> and resolve the report?">
                <?= csrf_field() ?>
                <button class="btn btn--danger btn--sm" type="submit">
                  <?= icon($r['subject_type'] === 'user' ? 'ban' : 'trash', '', 14) ?>
                  <?= $r['subject_type'] === 'user' ? 'Suspend account' : 'Remove content' ?>
                </button>
              </form>
            <?php endif; ?>
          </div>
        </footer>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
