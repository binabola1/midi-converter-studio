<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';
$user=require_login();
$fileId=(int)($_GET['file_id']??0);
$s=db()->prepare('SELECT * FROM files WHERE id=? AND user_id=? AND type="midi" AND status="active" LIMIT 1');$s->execute([$fileId,$user['id']]);$midi=$s->fetch();
if(!$midi){http_response_code(404);exit('MIDI tidak ditemukan.');}
$audio=null;
$q=db()->prepare('SELECT f.* FROM conversions c JOIN files f ON f.id=c.source_file_id WHERE c.output_file_id=? AND c.user_id=? AND f.status="active" LIMIT 1');$q->execute([$fileId,$user['id']]);$audio=$q->fetch()?:null;
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MIDI Studio Player</title>
<link rel="stylesheet" href="assets/css/midi-editor-pro.css"><link rel="stylesheet" href="assets/css/midi-studio-player.css"></head><body>
<header class="mpe-top"><div class="mpe-brand">🎚️ MIDI STUDIO PLAYER</div><button class="mpe-btn" id="back">Editor</button><button class="mpe-btn" id="play">▶ Play</button><button class="mpe-btn" id="pause">Ⅱ Pause</button><button class="mpe-btn" id="stop">■ Stop</button><button class="mpe-btn" id="loop">🔁 Loop</button><button class="mpe-btn" id="metro">Metronome</button><label>BPM <input id="bpm" class="mpe-input" type="number" min="20" max="300" style="width:65px"></label><span class="mpe-spacer"></span><span class="studio-time" id="time">0:00</span></header>
<main style="padding:12px;overflow:auto;height:calc(100vh - 58px)">
<section class="studio-player"><audio id="studioAudio" class="studio-audio" controls preload="metadata" <?php if($audio):?>src="api/files/stream.php?id=<?=json_encode((int)$audio['id'])?>"<?php endif;?>></audio>
<div class="studio-controls"><span>Audio Source: <?=e($audio['original_name']??'Tidak tersedia')?></span><label>Volume <input id="audioVol" type="range" min="0" max="1" step=".01" value=".8"></label></div>
<div class="studio-timeline"><div class="studio-playhead" id="playhead"></div></div>
<div class="studio-keyboard" id="keyboard"></div>
</section>
<section id="mix" style="margin-top:12px"></section>
</main>
<script src="assets/js/midi-studio-player.js"></script><script>
(async()=>{
const q=await fetch('api/midi/open.php?file_id=<?=json_encode($fileId)?>');const j=await q.json();if(!j.success){document.body.innerHTML='<p>'+j.message+'</p>';return}
const root=document.body;const p=new MidiStudioPlayer(j.data,root);document.getElementById('bpm').value=p.tempo;
document.getElementById('play').onclick=()=>p.play();document.getElementById('pause').onclick=()=>p.pause();document.getElementById('stop').onclick=()=>p.stop();
document.getElementById('loop').onclick=e=>{p.loop=!p.loop;e.currentTarget.classList.toggle('active',p.loop)};document.getElementById('metro').onclick=e=>{p.toggleMetronome();e.currentTarget.classList.toggle('active',p.metronome)};
document.getElementById('bpm').onchange=e=>p.tempo=Math.max(20,Math.min(300,Number(e.target.value)||120));
document.getElementById('audioVol').oninput=e=>{if(p.audio)p.audio.volume=Number(e.target.value)};
document.getElementById('back').onclick=()=>location.href='editor.php?file_id=<?=json_encode($fileId)?>';
p.createKeyboard(document.getElementById('keyboard'));
const mix=document.getElementById('mix');
j.data.tracks.forEach((t,i)=>{const d=document.createElement('div');d.className='studio-track-mix';d.innerHTML='<b>'+(i+1)+'. '+(t.name||'Track '+(i+1))+'</b><label>Volume <input type="range" min="0" max="1" step=".01" value="1"></label><label>Pan <input type="range" min="-1" max="1" step=".01" value="0"></label><button class="studio-btn">Mute</button><select class="mpe-select"></select>';const sel=d.querySelector('select');GM_INSTRUMENTS.forEach((n,k)=>{const o=document.createElement('option');o.value=k;o.textContent=k+' '+n;sel.appendChild(o)});sel.value=t.program||0;sel.onchange=e=>p.setInstrument(i,e.target.value);d.querySelectorAll('input')[0].oninput=e=>p.trackState[i].volume=Number(e.target.value);d.querySelectorAll('input')[1].oninput=e=>p.trackState[i].pan=Number(e.target.value);d.querySelector('button').onclick=e=>{p.trackState[i].mute=!p.trackState[i].mute;e.currentTarget.classList.toggle('active',p.trackState[i].mute)};mix.appendChild(d)});
})();
</script></body></html>