const GM_INSTRUMENTS=[
"Acoustic Grand Piano","Bright Acoustic Piano","Electric Grand Piano","Honky-tonk Piano","Electric Piano 1","Electric Piano 2","Harpsichord","Clavinet",
"Celesta","Glockenspiel","Music Box","Vibraphone","Marimba","Xylophone","Tubular Bells","Dulcimer",
"Drawbar Organ","Percussive Organ","Rock Organ","Church Organ","Reed Organ","Accordion","Harmonica","Tango Accordion",
"Acoustic Guitar (nylon)","Acoustic Guitar (steel)","Electric Guitar (jazz)","Electric Guitar (clean)","Electric Guitar (muted)","Overdriven Guitar","Distortion Guitar","Guitar Harmonics",
"Acoustic Bass","Electric Bass (finger)","Electric Bass (pick)","Fretless Bass","Slap Bass 1","Slap Bass 2","Synth Bass 1","Synth Bass 2",
"Violin","Viola","Cello","Contrabass","Tremolo Strings","Pizzicato Strings","Orchestral Harp","Timpani",
"String Ensemble 1","String Ensemble 2","SynthStrings 1","SynthStrings 2","Choir Aahs","Voice Oohs","Synth Voice","Orchestra Hit",
"Trumpet","Trombone","Tuba","Muted Trumpet","French Horn","Brass Section","SynthBrass 1","SynthBrass 2",
"Soprano Sax","Alto Sax","Tenor Sax","Baritone Sax","Oboe","English Horn","Bassoon","Clarinet",
"Piccolo","Flute","Recorder","Pan Flute","Blown Bottle","Shakuhachi","Whistle","Ocarina",
"Lead 1 (square)","Lead 2 (sawtooth)","Lead 3 (calliope)","Lead 4 (chiff)","Lead 5 (charang)","Lead 6 (voice)","Lead 7 (fifths)","Lead 8 (bass + lead)",
"Pad 1 (new age)","Pad 2 (warm)","Pad 3 (polysynth)","Pad 4 (choir)","Pad 5 (bowed)","Pad 6 (metallic)","Pad 7 (halo)","Pad 8 (sweep)",
"FX 1 (rain)","FX 2 (soundtrack)","FX 3 (crystal)","FX 4 (atmosphere)","FX 5 (brightness)","FX 6 (goblins)","FX 7 (echoes)","FX 8 (sci-fi)",
"Sitar","Banjo","Shamisen","Koto","Kalimba","Bag pipe","Fiddle","Shanai",
"Tinkle Bell","Agogo","Steel Drums","Woodblock","Taiko Drum","Melodic Tom","Synth Drum","Reverse Cymbal",
"Guitar Fret Noise","Breath Noise","Seashore","Bird Tweet","Telephone Ring","Helicopter","Applause","Gunshot",
"Applause","Helicopter","Gunshot","Reverse Cymbal"
];
class MidiStudioPlayer{
constructor(data,root){this.data=data;this.root=root;this.audio=null;this.ctx=null;this.master=null;this.timer=null;this.startPerf=0;this.startTick=0;this.tick=0;this.tempo=Number(data.tempo||120);this.metronome=false;this.loop=false;this.trackState=data.tracks.map(t=>({volume:1,pan:0,mute:false,solo:false,instrument:Number(t.program||0)}));this.bind()}
bind(){this.root.querySelectorAll('[data-instrument]').forEach(s=>s.onchange=e=>this.setInstrument(Number(s.dataset.instrument),Number(e.target.value)));const metro=this.root.querySelector('[data-metronome]');if(metro)metro.onclick=()=>this.toggleMetronome();const play=this.root.querySelector('[data-play]');if(play)play.onclick=()=>this.play();const pause=this.root.querySelector('[data-pause]');if(pause)pause.onclick=()=>this.pause();const stop=this.root.querySelector('[data-stop]');if(stop)stop.onclick=()=>this.stop();const loop=this.root.querySelector('[data-loop]');if(loop)loop.onclick=()=>{this.loop=!this.loop};const a=this.root.querySelector('#studioAudio');if(a){this.audio=a;a.ontimeupdate=()=>this.syncAudio();a.onplay=()=>this.startClock();a.onpause=()=>this.pause();a.onseeked=()=>this.syncAudio()}}
ensureAudio(){if(this.ctx)return;this.ctx=new(window.AudioContext||window.webkitAudioContext)();this.master=this.ctx.createGain();this.master.gain.value=.8;this.master.connect(this.ctx.destination)}
freq(p){return 440*Math.pow(2,(p-69)/12)}
setInstrument(i,v){if(this.trackState[i])this.trackState[i].instrument=Math.max(0,Math.min(127,v))}
note(p,v=100,d=.25,i=0){this.ensureAudio();const t=this.trackState[i]||{volume:1,pan:0,instrument:0};if(t.mute)return;const o=this.ctx.createOscillator(),g=this.ctx.createGain(),pan=this.ctx.createStereoPanner();const types=['triangle','sine','square','sawtooth'];o.type=types[Math.floor((t.instrument%types.length))];o.frequency.value=this.freq(p);g.gain.setValueAtTime(.0001,this.ctx.currentTime);g.gain.exponentialRampToValueAtTime(Math.max(.015,.16*v/127*t.volume),this.ctx.currentTime+.01);g.gain.exponentialRampToValueAtTime(.0001,this.ctx.currentTime+Math.max(.04,d));pan.pan.value=Math.max(-1,Math.min(1,t.pan));o.connect(g).connect(pan).connect(this.master);o.start();o.stop(this.ctx.currentTime+Math.max(.05,d)+.03)}
play(){this.ensureAudio();if(this.audio){this.audio.play().catch(()=>{});this.startClock();return}this.startClock()}
startClock(){if(this.timer)return;this.startPerf=performance.now();this.startTick=this.tick;this.timer=setInterval(()=>this.clock(),20)}
clock(){if(this.audio){this.tick=(this.audio.currentTime* this.tempo/60)* (this.data.ticks_per_beat||480)}else{this.tick=this.startTick+(performance.now()-this.startPerf)/60000*this.tempo*(this.data.ticks_per_beat||480)}this.renderPlayhead();if(this.metronome)this.metronomeTick();if(this.tick>=this.maxTick()){if(this.loop){this.tick=0;if(this.audio){this.audio.currentTime=0}}else this.stop()}}
pause(){if(this.audio)this.audio.pause();if(this.timer){clearInterval(this.timer);this.timer=null}}
stop(){this.pause();this.tick=0;this.renderPlayhead();this.allOff()}
allOff(){}
maxTick(){let m=0;this.data.tracks.forEach(t=>t.notes.forEach(n=>m=Math.max(m,n.start_tick+n.duration_ticks)));return m}
syncAudio(){if(!this.audio)return;this.tick=this.audio.currentTime*this.tempo/60*(this.data.ticks_per_beat||480);this.renderPlayhead()}
renderPlayhead(){const p=this.root.querySelector('[data-playhead]');if(p)p.style.left=(this.tick/(this.data.ticks_per_beat||480)*48)+'px';const time=this.tick/(this.data.ticks_per_beat||480)*60/this.tempo;const out=this.root.querySelector('[data-current-time]');if(out)out.textContent=this.fmt(time)}
fmt(s){return Math.floor(s/60)+':'+String(Math.floor(s%60)).padStart(2,'0')}
metronomeTick(){const beat=Math.floor(this.tick/(this.data.ticks_per_beat||480));if(beat!==this.lastBeat){this.lastBeat=beat;this.ensureAudio();const o=this.ctx.createOscillator(),g=this.ctx.createGain();o.frequency.value=beat%4===0?1200:800;g.gain.value=.05;o.connect(g).connect(this.master);o.start();o.stop(this.ctx.currentTime+.04)}}
toggleMetronome(){this.metronome=!this.metronome}
createKeyboard(el){for(let p=36;p<=96;p++){const k=document.createElement('button');k.textContent=this.name(p);k.onclick=()=>this.note(p,100,.5,0);el.appendChild(k)}}
name(p){return['C','C#','D','D#','E','F','F#','G','G#','A','A#','B'][p%12]+(Math.floor(p/12)-1)}
}
