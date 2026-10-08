<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';
require_login();
$csrf=csrf_token();
$fileId=(int)($_GET['file_id']??0);
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Professional MIDI Editor</title>
<link rel="stylesheet" href="assets/css/midi-editor-pro.css">
</head><body>
<header class="mpe-top">
  <div class="mpe-brand">🎹 MIDI CONVERTER STUDIO</div>
  <button class="mpe-btn" id="back">Dashboard</button>
  <button class="mpe-btn" id="play">▶ Play</button><button class="mpe-btn" id="pause">Ⅱ Pause</button><button class="mpe-btn" id="stop">■ Stop</button>
  <button class="mpe-btn" id="undo">↶</button><button class="mpe-btn" id="redo">↷</button>
  <label>BPM <input class="mpe-input" id="tempo" type="number" min="20" max="300" value="120" style="width:70px"></label>
  <label>Quantize <select class="mpe-select" id="quant"><option value="0.25">1/16</option><option value="0.5">1/8</option><option value="1">1/4</option><option value="2">1/2</option></select></label>
  <button class="mpe-btn" id="quantize">Quantize</button><button class="mpe-btn" id="transUp">Transpose +1</button><button class="mpe-btn" id="transDown">Transpose -1</button>
  <button class="mpe-btn" id="loop">🔁 Loop Off</button><label>Zoom <input id="zoom" type="range" min="20" max="120" value="48"></label>
  <div class="mpe-spacer"></div><button class="mpe-btn mpe-primary" id="save">💾 Simpan</button><button class="mpe-btn" id="download">⬇ Download</button>
</header>
<section class="mpe-main">
  <aside class="mpe-tracks" id="tracks"><div class="mpe-status">Memuat MIDI…</div></aside>
  <main class="mpe-work">
    <div class="mpe-toolbar">
      <button class="mpe-btn" id="add">＋ Tambah Not</button><button class="mpe-btn" id="delete">Hapus</button>
      <label>Velocity <input class="mpe-input" id="velocity" type="number" min="1" max="127" value="100" style="width:65px"></label>
      <span class="mpe-status" id="status">Memuat…</span>
    </div>
    <div class="mpe-scroll"><div class="mpe-roll" id="roll"><div class="mpe-gridline"></div><div class="mpe-keys" id="mpe-keys"></div></div></div>
    <div class="mpe-velocity"><div class="mpe-vcanvas" id="velCanvas"></div></div>
  </main>
</section>
<script src="assets/js/midi-editor-pro.js"></script>
<script>
(async()=>{
 const fileId=<?=json_encode($fileId)?>,csrf=<?=json_encode($csrf)?>;
 const q=await fetch('api/midi/open.php?file_id='+encodeURIComponent(fileId)); const j=await q.json();
 if(!j.success){document.getElementById('status').textContent=j.message;return}
 const ed=new MidiEditorPro({data:j.data,fileId,csrf,grid:document.getElementById('roll'),velCanvas:document.getElementById('velCanvas'),status:document.getElementById('status'),trackBox:document.getElementById('tracks')});
 document.getElementById('tempo').value=ed.tempo;
 document.getElementById('back').onclick=()=>location.href='dashboard.php';
 document.getElementById('add').onclick=()=>{ed.insertAt(0,300)};
 document.getElementById('delete').onclick=()=>ed.deleteSelected();
 document.getElementById('undo').onclick=()=>ed.undo(); document.getElementById('redo').onclick=()=>ed.redo();
 document.getElementById('transUp').onclick=()=>ed.transpose(1); document.getElementById('transDown').onclick=()=>ed.transpose(-1);
 document.getElementById('quantize').onclick=()=>ed.quantize(Number(document.getElementById('quant').value));
 document.getElementById('tempo').onchange=e=>ed.setTempo(e.target.value);
 document.getElementById('velocity').onchange=e=>ed.setVelocity(e.target.value);
 document.getElementById('zoom').oninput=e=>ed.setZoom(e.target.value);
 document.getElementById('loop').onclick=e=>{ed.loop.on=!ed.loop.on;e.target.textContent=ed.loop.on?'🔁 Loop On':'🔁 Loop Off'};
 document.getElementById('play').onclick=()=>ed.play(); document.getElementById('pause').onclick=()=>ed.pause(); document.getElementById('stop').onclick=()=>ed.stop();
 document.getElementById('save').onclick=async()=>await ed.save();
 document.getElementById('download').onclick=()=>location.href='api/files/download.php?id='+encodeURIComponent(fileId);
 document.addEventListener('keydown',e=>{if(e.target.matches('input,select'))return;if(e.ctrlKey&&e.key.toLowerCase()==='z'){e.preventDefault();ed.undo()}else if(e.ctrlKey&&e.key.toLowerCase()==='y'){e.preventDefault();ed.redo()}else if(e.key==='Delete')ed.deleteSelected();else if(e.key==='ArrowUp')ed.move(0,1);else if(e.key==='ArrowDown')ed.move(0,-1);else if(e.key==='ArrowLeft')ed.move(-.25,0);else if(e.key==='ArrowRight')ed.move(.25,0)});
})();
</script></body></html>