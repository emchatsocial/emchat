<?php /** @var array $user  @var string $section */ ?>
<div class="page-head"><h1>Settings</h1></div>
<?= $view->partial('settings_nav', ['section' => $section]) ?>

<section class="panel">
  <h2 class="panel__title">End-to-end encryption</h2>
  <p class="muted">Your one-to-one messages are encrypted in your browser before they're sent. Only you and the person you're chatting with can read them. EMChat can't.</p>
</section>

<div data-e2e-settings>
  <section class="panel"><p class="muted">Loading your encryption settings…</p></section>
</div>

<noscript><section class="panel"><p class="muted">Encryption settings need JavaScript.</p></section></noscript>
