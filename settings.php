<?php
declare(strict_types=1);
$config = require __DIR__ . '/config.php';
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Saved settings · <?= htmlspecialchars($config['app_name']) ?></title><link rel="stylesheet" href="assets/style.css"></head>
<body data-page="settings">
<header class="topbar">
  <div><h1>Saved settings</h1><p>Pre-configured trophies with saved engraving zones and defaults</p></div>
  <nav class="nav"><a href="index.php">Engraver</a><a href="library.php">Artwork library</a><a class="active" href="settings.php">Saved settings</a></nav>
</header>
<main class="page-shell">
  <div class="page-tools"><input id="pageSearch" type="search" placeholder="Search saved settings"></div>
  <div id="pageGrid" class="catalog-grid"></div>
</main>
<script src="assets/catalog.js"></script>
</body></html>