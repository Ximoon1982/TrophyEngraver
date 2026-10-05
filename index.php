<?php
declare(strict_types=1);
$config = require __DIR__ . '/config.php';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($config['app_name']) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <div><h1>Trophy Engraver</h1><p>Filesystem-backed trophy library and engraver</p></div>
  <button id="uploadBtn" class="primary">Upload trophy</button>
  <input id="uploadInput" type="file" accept="image/png,image/jpeg,image/webp" hidden>
</header>

<main class="layout">
  <aside class="library">
    <div class="library-head">
      <h2>Library</h2>
      <input id="search" type="search" placeholder="Search trophies">
      <select id="sort"><option value="updated">Recently updated</option><option value="name">Name</option><option value="newest">Newest</option><option value="oldest">Oldest</option></select>
    </div>
    <div id="libraryGrid" class="library-grid"></div>
  </aside>

  <section class="workspace">
    <div class="stage-wrap">
      <div id="emptyState" class="empty">Upload or select a trophy.</div>
      <canvas id="canvas"></canvas>
      <div class="stage-actions">
        <button id="zoneMode">Draw engraving zone</button>
        <button id="toggleZone">Hide zone</button>
        <button id="exportPng" class="primary">Export PNG</button>
      </div>
    </div>

    <div class="controls">
      <div class="panel">
        <h2>Engraving</h2>
        <div id="lines"></div>
        <label>Line spacing <input id="lineSpacing" type="range" min=".6" max="2" step=".05" value="1"></label>
        <label>Horizontal offset <input id="offsetX" type="number" step="1" value="0"></label>
        <label>Vertical offset <input id="offsetY" type="number" step="1" value="0"></label>
        <div class="nudges">
          <button data-nudge="0,-1">↑</button><button data-nudge="-1,0">←</button><button data-nudge="1,0">→</button><button data-nudge="0,1">↓</button>
        </div>
      </div>
      <div class="panel">
        <h2>Style</h2>
        <label>Font <select id="font"><option>Georgia</option><option>Times New Roman</option><option>Garamond</option><option>Palatino Linotype</option><option>Arial</option><option>Trebuchet MS</option></select></label>
        <label>Material <select id="material"><option value="gold">Gold</option><option value="silver">Silver</option><option value="bronze">Bronze</option><option value="dark">Dark</option><option value="light">Light</option></select></label>
        <label>Projection <select id="projection"><option value="flat">Flat</option><option value="cylindrical">Cylindrical</option></select></label>
        <label>Curve <input id="curve" type="range" min="-100" max="100" step="1" value="0"></label>
        <button id="saveDefaults" class="primary">Save trophy defaults</button>
      </div>
      <div class="panel">
        <h2>Selected trophy</h2>
        <div id="selectedMeta">None</div>
        <div class="row"><button id="renameBtn">Rename</button><button id="deleteBtn" class="danger">Delete</button></div>
      </div>
    </div>
  </section>
</main>
<script src="assets/app.js"></script>
</body>
</html>
