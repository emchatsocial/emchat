<?php
/** @var array $meta */
$m = ($meta ?? []) + [
    'title'       => config('app_name'),
    'description' => config('app_tagline'),
    'canonical'   => url('/'),
    'robots'      => 'index,follow',
    'og_image'    => url('/assets/img/og-default.png'),
    'type'        => 'website',
    'jsonld'      => null,
];
$user = current_user();
?>
<!-- Google tag (gtag.js) -->
<script async nonce="<?= e(csp_nonce()) ?>" src="https://www.googletagmanager.com/gtag/js?id=G-GCB737L718"></script>
<script nonce="<?= e(csp_nonce()) ?>">
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-GCB737L718');
</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#f5f5f3" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#161719" media="(prefers-color-scheme: dark)">
<title><?= e($m['title']) ?></title>
<meta name="description" content="<?= e($m['description']) ?>">
<meta name="robots" content="<?= e($m['robots']) ?>">
<link rel="canonical" href="<?= e($m['canonical']) ?>">
<link rel="icon" href="/favicon.ico?v=64" sizes="any">
<link rel="icon" href="/assets/img/favicon.svg?v=64" type="image/svg+xml">
<link rel="icon" href="/assets/img/favicon-48.png?v=64" sizes="48x48" type="image/png">
<link rel="icon" href="/assets/img/favicon-16.png?v=64" sizes="16x16" type="image/png">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png?v=64">
<link rel="manifest" href="/assets/site.webmanifest">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="EMChat">
<meta name="geo.region" content="MK">
<meta name="geo.placename" content="North Macedonia">

<meta property="og:site_name" content="<?= e(config('app_name')) ?>">
<meta property="og:type" content="<?= e($m['type']) ?>">
<meta property="og:title" content="<?= e($m['title']) ?>">
<meta property="og:description" content="<?= e($m['description']) ?>">
<meta property="og:url" content="<?= e($m['canonical']) ?>">
<meta property="og:image" content="<?= e($m['og_image']) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($m['title']) ?>">
<meta name="twitter:description" content="<?= e($m['description']) ?>">
<meta name="twitter:image" content="<?= e($m['og_image']) ?>">

<link rel="preload" href="/assets/fonts/Michroma.woff2?v=1" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/Bricolage.woff2?v=1" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/Hanken.woff2?v=1" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/css/app.css?v=86" as="style">
<link rel="stylesheet" href="/assets/css/app.css?v=86">
<script nonce="<?= e(csp_nonce()) ?>">
(function(){var d=document.documentElement;try{var t=localStorage.getItem('emc-theme');d.dataset.theme=(t==='light'||t==='dark')?t:'dark';}catch(e){d.dataset.theme='dark';}
try{d.classList.add('js');}catch(e){}})();
</script>
<?php if (!empty($m['jsonld'])): ?>
<script type="application/ld+json"><?= str_replace('</', '<\/', $m['jsonld']) /* pre-encoded JSON; escape </ so user text (bio, post body, ...) can never close this tag early */ ?></script>
<?php endif; ?>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
