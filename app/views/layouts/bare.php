<?php
/** @var string $content  @var array $meta */
?><!doctype html>
<html lang="en" data-theme="dark">
<head><?= $view->renderPartial('partials/head', ['meta' => $meta ?? []]) ?></head>
<body class="bare">
<main id="main" class="bare__wrap"><?= $content ?></main>
<script src="/assets/js/app.js?v=68" defer></script>
</body>
</html>
