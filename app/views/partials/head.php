<?php
/** @var array $meta */
$m = ($meta ?? []) + [
    'title'       => config('app_name'),
    'description' => config('app_tagline'),
    'canonical'   => url('/'),
    'robots'      => 'index,follow',
    'og_image'    => url('/assets/img/og-default.png?v=3'),
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
  // Consent Mode: advertising stays off unless the visitor accepts (see below).
  gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });

  gtag('config', 'G-GCB737L718');
</script>
<?php if (!$user): ?>
<!-- Google Ads via Tag Manager: public pages only, loaded only after the visitor accepts cookies. -->
<script nonce="<?= e(csp_nonce()) ?>">
(function () {
  var GTM = 'GTM-WRS7LGSN', NONCE = <?= json_encode(csp_nonce()) ?>, loaded = false;
  function choice() { var m = document.cookie.match(/(?:^|; )emc_ads=(yes|no)/); return m ? m[1] : null; }
  function remember(v) { document.cookie = 'emc_ads=' + v + '; Max-Age=15552000; Path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : ''); }
  function load() {
    if (loaded) return; loaded = true;
    gtag('consent', 'update', { ad_storage: 'granted', ad_user_data: 'granted', ad_personalization: 'granted' });
    dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
    var s = document.createElement('script'); s.async = true; s.nonce = NONCE;
    s.src = 'https://www.googletagmanager.com/gtm.js?id=' + GTM; document.head.appendChild(s);
  }
  function clearAdCookies() {
    document.cookie.split('; ').forEach(function (c) {
      var n = c.split('=')[0];
      if (/^(_gcl_|_gac_)/.test(n)) {
        ['', '; Domain=' + location.hostname, '; Domain=.' + location.hostname.replace(/^www\./, '')].forEach(function (d) {
          document.cookie = n + '=; Max-Age=0; Path=/' + d;
        });
      }
    });
  }
  function bar() {
    var old = document.getElementById('cookie-bar'); if (old) old.remove();
    var el = document.createElement('div'); el.id = 'cookie-bar'; el.className = 'cookie-bar';
    el.setAttribute('role', 'dialog'); el.setAttribute('aria-label', 'Cookie choice');
    var p = document.createElement('p');
    p.innerHTML = 'Can we use Google Ads cookies on our public pages? They show us whether our ads on Google bring people to EMChat. You won\u2019t see ads on EMChat either way. <a href="/privacy#cookies">Learn more</a>';
    var row = document.createElement('div'); row.className = 'cookie-bar__btns';
    [['Decline', 'no'], ['Accept', 'yes']].forEach(function (b) {
      var btn = document.createElement('button'); btn.type = 'button';
      btn.className = 'btn btn--sm ' + (b[1] === 'yes' ? 'btn--primary' : 'btn--ghost'); btn.textContent = b[0];
      btn.addEventListener('click', function () {
        var before = choice(); remember(b[1]); el.remove();
        if (b[1] === 'yes') { load(); }
        else if (before === 'yes') { clearAdCookies(); location.reload(); }
      });
      row.appendChild(btn);
    });
    el.appendChild(p); el.appendChild(row); document.body.appendChild(el);
  }
  if (choice() === 'yes') load();
  document.addEventListener('DOMContentLoaded', function () { if (choice() === null) bar(); });
  document.addEventListener('click', function (e) {
    var t = e.target.closest && e.target.closest('[data-cookie-settings]');
    if (t) { e.preventDefault(); bar(); }
  });
})();
</script>
<?php endif; ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#f5f5f3" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0f1319" media="(prefers-color-scheme: dark)">
<title><?= e($m['title']) ?></title>
<meta name="description" content="<?= e($m['description']) ?>">
<meta name="robots" content="<?= e($m['robots']) ?>">
<link rel="canonical" href="<?= e($m['canonical']) ?>">
<link rel="icon" href="/favicon.ico?v=67" sizes="any">
<link rel="icon" href="/assets/img/favicon-48.png?v=67" sizes="48x48" type="image/png">
<link rel="icon" href="/assets/img/favicon-16.png?v=66" sizes="16x16" type="image/png">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png?v=67">
<link rel="manifest" href="/assets/site.webmanifest">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="EMChat">
<meta name="geo.region" content="MK">
<meta name="geo.placename" content="North Macedonia">

<meta property="og:site_name" content="<?= e(config('app_name')) ?>">
<meta property="og:locale" content="en_US">
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
<link rel="preload" href="/assets/css/app.css?v=109" as="style">
<link rel="stylesheet" href="/assets/css/app.css?v=109">
<script nonce="<?= e(csp_nonce()) ?>">
(function(){var d=document.documentElement;try{var t=localStorage.getItem('emc-theme');d.dataset.theme=(t==='light'||t==='dark')?t:'dark';}catch(e){d.dataset.theme='dark';}
try{d.classList.add('js');}catch(e){}})();
</script>
<?php if (!empty($m['jsonld'])): ?>
<script type="application/ld+json"><?= str_replace('</', '<\/', $m['jsonld']) /* pre-encoded JSON; escape </ so user text (bio, post body, ...) can never close this tag early */ ?></script>
<?php endif; ?>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<?php $e2eUser = current_user(); if ($e2eUser && \App\Models\E2E::enabledFor($e2eUser)): ?>
<meta name="emc-e2e" content="<?= (int) $e2eUser['id'] ?>">
<?php endif; ?>
