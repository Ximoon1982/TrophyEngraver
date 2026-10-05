<?php declare(strict_types=1); $config=require __DIR__.'/config.php'; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= htmlspecialchars($config['app_name']) ?></title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="app">
  <header class="topbar">
    <div class="brand"><h1>Trophy Engraver</h1><p>Large trophy view, focused plate preview, compact controls.</p></div>
    <nav class="nav"><a class="active" href="index.php">Engraver</a><a href="library.php">Artwork library</a><a href="settings.php">Saved settings</a></nav><div class="toolbar"><button id="newTrophyBtn" class="btn primary">New trophy</button><button id="saveSettingsBtn" class="btn">Save settings</button><button id="fullPreviewBtn" class="btn">Full preview</button><button id="exportPng" class="btn primary">Export PNG</button></div><input id="fileInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden">
  </header>

  <main class="main">
    <section class="panel">
      <div class="panelHeader"><div><h2>Trophy preview</h2><div class="muted">Large overview for context.</div></div><span id="dimPill" class="statusPill">No image</span></div>
      <div class="panelBody overviewBody">
        <div class="overviewCanvasWrap">
          <canvas id="overviewCanvas" class="hidden"></canvas>
          <div id="overviewEmpty" class="overviewEmpty">Upload a trophy image to start.</div>
        </div>
        <div class="overviewActions">
          <button id="drawZoneBtn" class="btn primary">Select engraving zone</button>
          <button id="clearZoneBtn" class="btn">Clear zone</button><label class="toggleLine"><input id="showZone" type="checkbox" checked> Show engraving zone</label>
        </div>
        <div id="zoneReadout" class="zoneReadout">No engraving zone.</div>
      </div>
    </section>

    <section class="panel">
      <div class="panelHeader"><div><h2>Plate preview</h2><div class="muted">Zoomed engraving area.</div></div><div><span id="modePill" class="statusPill">Flat plate</span> <span id="materialPill" class="statusPill">Gold</span></div></div>
      <div class="panelBody centerBody">
        <div class="zoomCanvasWrap">
          <canvas id="plateCanvas" class="hidden"></canvas>
          <div id="zoomEmpty" class="zoomEmpty">Select an engraving zone to see the plate close-up.</div>
        </div>
        <div id="textRows" class="textRows"></div>
      </div>
    </section>

    <section class="panel">
      <div class="panelHeader"><div><h2>Engraving controls</h2><div class="muted">Common options stay visible; less-used tuning is folded.</div></div></div>
      <div class="panelBody controlsBody compact">
        <div class="controlGrid">
          <div class="field"><label>Font</label><select id="font"><option>Georgia</option><option>Times New Roman</option><option>Garamond</option><option>Palatino Linotype</option><option>Arial</option><option>Trebuchet MS</option></select></div>
          <div class="field"><label>Interline</label><input id="interline" type="number" value="8" step="1"></div>
        </div>

        <div class="field"><label>Material</label><div class="swatches" id="materialSwatches">
          <button class="swatch active" data-material="gold">Gold</button><button class="swatch" data-material="silver">Silver</button><button class="swatch" data-material="bronze">Bronze</button><button class="swatch" data-material="dark">Dark</button><button class="swatch" data-material="light">Light</button>
        </div></div>

        <div class="controlGrid">
          <div class="field"><label>Surface shape</label><select id="surface"><option value="flat">Flat plate</option><option value="cyl">Curved plate</option></select></div>
          <div class="field"><label>Nudge step</label><input id="nudgeStep" type="number" min="1" value="1"></div>
        </div>

        <div id="curveRow" class="sliderRow hidden"><label>Wrap around curve</label><input id="curve" type="range" min="0" max="100" step="1" value="28"><span id="curveValue" class="sliderValue">28</span></div>
        <div id="curveHelp" class="helper hidden">Simulates engraving wrapped over a curved plaque. The baseline stays straight.</div>

        <div class="positionRow"><span>X</span><input id="offsetX" type="number" value="0" step="1"><span>Y</span><input id="offsetY" type="number" value="0" step="1"></div>
        <div class="nudges"><span class="blank"></span><button data-nudge="0,-1">↑</button><span class="blank"></span><button data-nudge="-1,0">←</button><button data-nudge="0,1">↓</button><button data-nudge="1,0">→</button></div>

        <div class="sliderRow"><label>Opacity</label><input id="opacity" type="range" min="0" max="1" step="0.01" value="0.82"><span id="opacityValue" class="sliderValue">82%</span></div>
        <div class="sliderRow"><label>Tone</label><input id="tone" type="range" min="-50" max="50" step="1" value="-10"><span id="toneValue" class="sliderValue">-10</span></div>
        <div class="sliderRow"><label>Emboss / recess</label><input id="emboss" type="range" min="-8" max="8" step="1" value="2"><span id="embossValue" class="sliderValue">+2</span></div>

        <details>
          <summary>Advanced</summary>
          <div class="detailsBody compact">
            <div class="sliderRow"><label>Edge contrast</label><input id="contrast" type="range" min="0" max="1" step="0.01" value="0.38"><span id="contrastValue" class="sliderValue">38%</span></div>
            <div class="sliderRow"><label>Highlight</label><input id="highlight" type="range" min="0" max="1" step="0.01" value="0.34"><span id="highlightValue" class="sliderValue">34%</span></div>
            <div class="sliderRow"><label>Shadow</label><input id="shadow" type="range" min="0" max="1" step="0.01" value="0.36"><span id="shadowValue" class="sliderValue">36%</span></div>
            <div class="controlGrid">
              <div class="field"><label>Global scale</label><input id="textScale" type="number" step="0.01" value="1"></div>
              <div class="field"><label>Tracking</label><input id="tracking" type="number" step="0.1" value="0"></div>
            </div>
          </div>
        </details>
      </div>
    </section>
  </main>
</div>

<div id="newModal" class="modal hidden"><div class="modalCard" style="width:min(94vw,1050px);height:auto;max-height:92vh"><div class="modalHead"><strong>New trophy</strong><div class="actions"><button id="closeNewModal" class="btn small">Close</button></div></div><div class="modalBody" style="display:block;overflow:auto;padding:16px"><div id="choiceArea" class="chooserGrid"><button id="chooseConfigured" class="choiceBtn"><strong>Pre-configured trophy</strong><span>Open artwork together with a saved engraving zone and rendering settings.</span></button><button id="chooseUpload" class="choiceBtn"><strong>Upload a file</strong><span>Add a PNG, JPEG or WebP image to the stored artwork library.</span></button><button id="chooseStored" class="choiceBtn"><strong>Stored image</strong><span>Choose any bundled default or previously uploaded artwork.</span></button></div><div id="pickerArea" class="hidden"><div class="saveLine"><button id="pickerBack" class="btn small">Back</button><input id="pickerSearch" type="search" placeholder="Search artwork" style="flex:1;background:#10151c;color:var(--text);border:1px solid var(--line);border-radius:9px;padding:8px"></div><div id="pickerGrid" class="pickerGrid"></div></div></div></div></div>
<div id="previewModal" class="modal hidden">
  <div class="modalCard">
    <div class="modalHead"><strong>Full preview</strong><button id="closePreview" class="btn small">Close</button></div>
    <div class="modalBody"><img id="modalImg" alt="Full trophy preview"></div>
  </div>
</div>

<div id="zoneModal" class="modal hidden">
  <div class="modalCard">
    <div class="modalHead"><div><strong>Select engraving zone</strong><span id="zoneModalReadout" class="muted" style="margin-left:10px;font-size:11px">Drag to draw, then adjust corners.</span></div><div class="actions"><button id="cancelZone" class="btn small">Cancel</button><button id="applyZone" class="btn small primary">Apply</button></div></div>
    <div class="zoneModalBody">
      <div id="zoneStage" class="zoneStage">
        <img id="zoneImg" alt="Trophy artwork">
        <div id="zoneOverlay" class="zoneOverlay"></div>
      </div>
    </div>
  </div>
</div>

<script src="assets/app.js"></script></body></html>