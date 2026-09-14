<?php
/**
 * EMChat Media — configuration
 *
 * Copy this file to config/config.php and fill in your values, OR leave it as
 * is and put your secrets in a .env file next to this project (see .env.example).
 * The installer (install.php) can also generate config/config.php for you.
 */

return [
    // Public base URL, no trailing slash.
    'app_url'      => env('APP_URL', 'https://emchat.social'),
    'app_name'     => env('APP_NAME', 'EMChat Media'),
    'app_tagline'  => 'A calmer social network that keeps your data private',

    // Set to false on production. When true, magic-links are shown on screen
    // and errors are printed.
    'debug'        => env('APP_DEBUG', false),

    // 32+ random chars. Used for signing tokens. Keep secret.
    'app_key'      => env('APP_KEY', 'CHANGE_ME_TO_A_LONG_RANDOM_STRING_32_CHARS_MIN'),

    'db' => [
        'host'    => env('DB_HOST', 'localhost'),
        'name'    => env('DB_NAME', 'emchat'),
        'user'    => env('DB_USER', 'emchat'),
        'pass'    => env('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],

    // Outgoing mail for magic links.
    // driver: 'resend' (HTTPS API, recommended), 'smtp', 'mail' (PHP mail()),
    // or 'log' (writes .eml files to storage/mail and never sends — local dev).
    // Defaults to 'resend' automatically when RESEND_API_KEY is set.
    'mail' => [
        'driver'     => env('MAIL_DRIVER', env('RESEND_API_KEY') ? 'resend' : 'log'),
        'from_email' => env('RESEND_FROM', env('MAIL_FROM', 'no-reply@emchat.social')),
        'from_name'  => env('MAIL_FROM_NAME', 'EMChat Media'),
        'resend' => [
            'api_key' => env('RESEND_API_KEY', ''),
        ],
        'smtp' => [
            'host'       => env('SMTP_HOST', 'mail.emchat.social'),
            'port'       => (int) env('SMTP_PORT', 465),
            'encryption' => env('SMTP_ENCRYPTION', 'ssl'),  // 'ssl' for 465, 'tls' for 587
            'username'   => env('SMTP_USER', 'hello@emchat.social'),
            'password'   => env('SMTP_PASS', ''),
        ],
    ],

    // Absolute path to the writable storage directory.
    'storage_path' => dirname(__DIR__) . '/storage',

    // Absolute path to the web-accessible media directory (uploads live here).
    'media_path'   => dirname(__DIR__) . '/public/media',
    'media_url'    => '/media',

    // Upload limits
    'max_upload_bytes'   => 8 * 1024 * 1024,    // 8 MB per image
    'max_video_bytes'    => 40 * 1024 * 1024,   // 40 MB per video (mind PHP upload_max_filesize)
    'max_file_bytes'     => 20 * 1024 * 1024,   // 20 MB per document/other file
    'max_attach_permsg'  => 6,
    'max_images_perpost' => 4,
    'image_max_edge'     => 1600,              // px, long edge; images are re-encoded
    'avatar_edge'        => 400,

    // Magic-link lifetime (seconds)
    'login_token_ttl'    => 900,               // 15 minutes

    // Rate limiting for login requests
    'login_rate' => ['max' => 5, 'per_seconds' => 3600],

    // Anti-spam: caps posts+comments and messages per user, independent of content checks.
    'post_rate'    => ['max' => 15, 'per_seconds' => 300],
    'message_rate' => ['max' => 60, 'per_seconds' => 300],
];
