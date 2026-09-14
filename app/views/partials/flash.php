<?php
/** Renders the pending flash as a progressive toast (JS upgrades it; no-JS shows the bar). */
$f = flash();
if (!$f) {
    return;
}
$type = in_array($f['type'], ['success', 'error', 'info'], true) ? $f['type'] : 'success';
?>
<div class="flash flash--<?= $type ?>" role="status" data-flash data-type="<?= $type ?>"><?= e($f['message']) ?></div>
