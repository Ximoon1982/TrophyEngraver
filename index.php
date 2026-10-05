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
  <div><h1>Trophy Engraver</h1><p>Create and export engraved trophy artwork</p></div>
  <nav class="nav">
    <a class="active" href="index.php">Engraver</a>
    <a href="library.php">Artwork library</a>
    <a href="settings.php">Saved settings</a>
  </nav>
  <button id="newTrophyBtn" class="primary">New trophy</button>
</header>

<main class="engraver-page">
  <section class="workspace workspace-full">
    <div class="stage-wrap">
      <div id="emptyState" class="empty">
        <h2>No trophy selected</h2>
        <p>Create a new trophy to begin.</p>
        <button id="emptyNewBtn" class="primary">New trophy</button>
      </div>
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
        <button id="saveDefaults" class="primary">Save settings for this trophy</button>
      </div>
      <div class="panel">
        <h2>Selected trophy</h2>
        <div id="selectedMeta">None</div>
      </div>
    </div>
  </section>
</main>

<div id="newDialog" class="modal-backdrop" hidden>
  <div class="modal">
    <div class="modal-head"><div><h2>New trophy</h2><p>Choose how to start.</p></div><button id="closeNewDialog">×</button></div>
    <div class="choice-grid">
      <button class="choice" id="chooseConfigured"><strong>Pre-configured trophy</strong><span>Use artwork with a saved engraving zone and settings.</span></button>
      <button class="choice" id="chooseUpload"><strong>Upload a file</strong><span>Add a new PNG, JPEG or WebP artwork.</span></button>
      <button class="choice" id="chooseStored"><strong>Stored image</strong><span>Open artwork already present in the library.</span></button>
    </div>
    <div id="pickerArea" class="picker-area" hidden>
      <div class="picker-tools"><input id="pickerSearch" type="search" placeholder="Search"><button id="pickerBack">Back</button></div>
      <div id="pickerGrid" class="library-grid wide"></div>
    </div>
  </div>
</div>
<input id="uploadInput" type="file" accept="image/png,image/jpeg,image/webp" hidden>
<script src="assets/app.js"></script>
</body>
</html>