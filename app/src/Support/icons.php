<?php
declare(strict_types=1);

/**
 * Inline SVG icon set (stroke style, 24x24, currentColor).
 * Keeps the UI free of emoji and avoids any external requests.
 */

/** @var array<string,string> $EMCHAT_ICONS  name => inner SVG markup */
$GLOBALS['EMCHAT_ICONS'] = [
    'home'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h5v-6h4v6h5V9.5"/>',
    'compass'  => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5 5-2Z"/>',
    'bell'     => '<path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 20a2 2 0 0 0 4 0"/>',
    'bell-off' => '<path d="M8.7 5A6 6 0 0 1 18 9c0 3 .8 4.6 1.5 5.5M5 8.5c0 4-2 6-2 6h13M10 20a2 2 0 0 0 4 0"/><path d="m3 3 18 18"/>',
    'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7 8 6 8-6"/>',
    'send'     => '<path d="M22 3 3 10.5l7 2.5 2.5 7L22 3Z"/><path d="M10 13 22 3"/>',
    'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 5a3.5 3.5 0 0 1 0 7M22 20a6 6 0 0 0-4-5.7"/>',
    'settings' => '<line x1="4" y1="6" x2="20" y2="6"/><circle cx="8" cy="6" r="2" fill="currentColor"/>'
        . '<line x1="4" y1="12" x2="20" y2="12"/><circle cx="16" cy="12" r="2" fill="currentColor"/>'
        . '<line x1="4" y1="18" x2="20" y2="18"/><circle cx="10" cy="18" r="2" fill="currentColor"/>',
    'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    'plus'     => '<path d="M12 5v14M5 12h14"/>',
    'image'    => '<rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="8.5" cy="9.5" r="1.8"/><path d="m4 18 5-5 4 3 3.5-3.5L21 17"/>',
    'paperclip'=> '<path d="M20 11.5 12 19a5 5 0 0 1-7-7l8-8a3.5 3.5 0 0 1 5 5l-8 8a2 2 0 0 1-3-3l7-7"/>',
    'reply'    => '<path d="M9 15 4 10l5-5"/><path d="M4 10h9a7 7 0 0 1 7 7v2"/>',
    'comment'  => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-5A8 8 0 1 1 21 12Z"/>',
    'heart'    => '<path d="M12 20s-7-4.4-9.3-8.6C1 8 2.6 4.5 6 4.5c2 0 3.3 1.1 4 2.2.7-1.1 2-2.2 4-2.2 3.4 0 5 3.5 3.3 6.9C19 15.6 12 20 12 20Z"/>',
    'heart-fill'=> '<path d="M12 20s-7-4.4-9.3-8.6C1 8 2.6 4.5 6 4.5c2 0 3.3 1.1 4 2.2.7-1.1 2-2.2 4-2.2 3.4 0 5 3.5 3.3 6.9C19 15.6 12 20 12 20Z" fill="currentColor" stroke="none"/>',
    'link'     => '<path d="M9 15 15 9"/><path d="M11 6.5 12.8 4.7a4 4 0 0 1 5.6 5.6L16.5 12"/><path d="M13 17.5 11.2 19.3a4 4 0 0 1-5.6-5.6L7.5 12"/>',
    'more'     => '<circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/>',
    'pencil'   => '<path d="M15 5.5 18.5 9M4 20l1-4L16 5a2.1 2.1 0 0 1 3 3L8 19l-4 1Z"/>',
    'trash'    => '<path d="M4 7h16M10 4h4M6 7l1 13h10l1-13M10 11v6M14 11v6"/>',
    'x'        => '<path d="M6 6 18 18M18 6 6 18"/>',
    'check'    => '<path d="m4 12 5 5L20 6"/>',
    'arrow-left'=> '<path d="M20 12H4M10 6l-6 6 6 6"/>',
    'arrow-right'=> '<path d="M4 12h16M14 6l6 6-6 6"/>',
    'download'  => '<path d="M12 3v12M7 11l5 5 5-5M5 20h14"/>',
    'lock'     => '<rect x="4.5" y="10.5" width="15" height="11" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/>',
    'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 4 6 4 9s-1.5 6.4-4 9c-2.5-2.6-4-6-4-9s1.5-6.4 4-9Z"/>',
    'copy'     => '<rect x="9" y="9" width="12" height="12" rx="2.5"/><path d="M15 5.5A2.5 2.5 0 0 0 12.5 3h-7A2.5 2.5 0 0 0 3 5.5v7A2.5 2.5 0 0 0 5.5 15"/>',
    'ban'      => '<circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/>',
    'flag'     => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
    'theme'    => '<circle cx="12" cy="12" r="4"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6 7 7M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4"/>',
    'logout'   => '<path d="M9 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3"/><path d="M15 12H9M17 8l4 4-4 4"/>',
    'sparkle'  => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/>',
    'shield'   => '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
    'verified' => '<path d="m12 2 2.4 2.1 3.2-.3.6 3.1 2.8 1.6-1.4 2.9 1.4 2.9-2.8 1.6-.6 3.1-3.2-.3L12 22l-2.4-2.1-3.2.3-.6-3.1L3 15.5l1.4-2.9L3 9.7l2.8-1.6.6-3.1 3.2.3Z" fill="currentColor" stroke="none"/><path d="m8.5 12.2 2.3 2.3 4.7-4.7" stroke="var(--card)" stroke-width="2.2"/>',
    'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
    'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
    'crown'    => '<path d="M4 18h16M4 18 3 7l5 4 4-6 4 6 5-4-1 11"/>',
    'user-plus'=> '<circle cx="9" cy="8" r="3.5"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M18 8v6M15 11h6"/>',
    'user-minus'=> '<circle cx="9" cy="8" r="3.5"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M15 11h6"/>',
    'camera'   => '<path d="M4 8h3l2-3h6l2 3h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"/><circle cx="12" cy="13" r="3.5"/>',
    'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
];

if (!function_exists('icon')) {
    function icon(string $name, string $class = '', int $size = 20): string
    {
        $inner = $GLOBALS['EMCHAT_ICONS'][$name] ?? '';
        if ($inner === '') {
            return '';
        }
        return sprintf(
            '<svg class="ic %s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" '
            . 'stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
            e($class),
            $size,
            $size,
            $inner
        );
    }
}
