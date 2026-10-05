<?php
declare(strict_types=1);
$config = require __DIR__ . '/config.php';
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Artwork library · <?= htmlspecialchars($config['app_name']) ?></title><link rel="stylesheet" href="assets/style.css"></head>
<body data-page="library">
<header class="topbar">
  <div><h1>Artwork library</h1><p>Stored trophy artwork</p></div>
  <nav class="nav"><a href="index.php">Engraver</a><a class="active" href="library.php">Artwork library</a><a href="settings.php">Saved settings</a></nav>
  <button id="pageUploadBtn" class="primary">Upload artwork</button>
</header>
<main class="page-shell">
  <div class="page-tools"><input id="pageSearch" type="search" placeholder="Search artwork"><select id="pageSort"><option value="updated">Recently updated</option><option value="name">Name</option><option value="newest">Newest</option><option value="oldest">Oldest</option></select></div>
  <div id="pageGrid" class="catalog-grid"></div>
</main>
<input id="pageUploadInput" type="file" accept="image/png,image/jpeg,image/webp" hidden>
<script src="assets/catalog.js"></script>
</body></html>