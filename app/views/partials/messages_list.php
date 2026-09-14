<?php
/** @var array $messages  @var array $user  @var bool $isGroup */
$isGroup = $isGroup ?? false;
$RUN_GAP = 1200; // 20 min — bigger gaps break a run and show a time label

$prevSender = null;
$prevTs = 0;
$lastDay = null;
$count = count($messages);

foreach ($messages as $i => $m):
    $ts = strtotime($m['created_at']);
    $day = date('Y-m-d', $ts);
    $isSystem = ($m['kind'] ?? 'text') === 'system';

    if ($day !== $lastDay):
        $lastDay = $day;
        $prevSender = null;
        ?><div class="msg-day" data-day-label="<?= e($day) ?>"><?= e(date('l, M j', $ts)) ?></div><?php
    elseif (!$isSystem && $ts - $prevTs > $RUN_GAP):
        $prevSender = null;
        ?><div class="dm-timesep"><?= e(date('g:i A', $ts)) ?></div><?php
    endif;

    $next       = $messages[$i + 1] ?? null;
    $nextTs     = $next ? strtotime($next['created_at']) : 0;
    $nextSystem = $next && (($next['kind'] ?? 'text') === 'system');

    $runStart = $isSystem || (int) $m['sender_id'] !== (int) $prevSender;
    $runEnd   = $isSystem || !$next || $nextSystem
        || (int) $next['sender_id'] !== (int) $m['sender_id']
        || date('Y-m-d', $nextTs) !== $day
        || $nextTs - $ts > $RUN_GAP;

    echo $view->partial('message_item', [
        'm' => $m, 'user' => $user, 'isGroup' => $isGroup,
        'runStart' => $runStart, 'runEnd' => $runEnd,
    ]);

    $prevSender = $isSystem ? null : (int) $m['sender_id'];
    $prevTs = $ts;
endforeach;
