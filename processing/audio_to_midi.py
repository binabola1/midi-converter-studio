#!/usr/bin/env python3
"""Real audio-to-MIDI analysis engine.
Dependencies: numpy, scipy, mido. FFmpeg must be installed and available in PATH.
"""
from __future__ import annotations
import argparse, json, math, subprocess, tempfile
from pathlib import Path
import numpy as np
from mido import MidiFile, MidiTrack, Message, MetaMessage, bpm2tempo

SR=22050
TPB=480
MODES={"melody","polyphonic","piano","bass","vocals","percussion","automatic"}

def ffmpeg_pcm(src:Path)->np.ndarray:
    cmd=["ffmpeg","-v","error","-i",str(src),"-ac","1","-ar",str(SR),"-f","f32le","-"]
    raw=subprocess.check_output(cmd)
    return np.frombuffer(raw,dtype=np.float32)

def frame_signal(y, n=2048, hop=512):
    if len(y)<n: y=np.pad(y,(0,n-len(y)))
    count=1+max(0,(len(y)-n)//hop)
    return np.stack([y[i*hop:i*hop+n] for i in range(count)])

def hz_to_midi(f): return 69+12*np.log2(np.maximum(f,1e-9)/440.0)
def midi_to_hz(p): return 440.0*2**((p-69)/12)

def estimate_bpm(y):
    frames=frame_signal(y); energy=np.sqrt(np.mean(frames**2,axis=1)+1e-12)
    flux=np.maximum(0,np.diff(energy,prepend=energy[0]))
    flux=(flux-flux.mean())/(flux.std()+1e-9)
    lo=int(60*SR/(180*512)); hi=int(60*SR/(60*512))
    if hi<=lo or len(flux)<hi+2:return 120
    ac=np.correlate(flux,flux,mode="full")[len(flux)-1:]
    k=lo+int(np.argmax(ac[lo:hi+1])); bpm=60*SR/(k*512)
    while bpm<70:bpm*=2
    while bpm>180:bpm/=2
    return int(round(np.clip(bpm,60,180)))

def onset_frames(y):
    frames=frame_signal(y); win=np.hanning(frames.shape[1]); spec=np.abs(np.fft.rfft(frames*win,axis=1))
    log=np.log1p(spec); diff=np.maximum(0,np.diff(log,axis=0,prepend=log[:1]))
    env=diff.mean(axis=1); env=(env-env.mean())/(env.std()+1e-9)
    threshold=max(.15,float(np.percentile(env,70)))
    peaks=[]
    for i in range(1,len(env)-1):
        if env[i]>=threshold and env[i]>=env[i-1] and env[i]>=env[i+1]:
            if not peaks or i-peaks[-1]>=2: peaks.append(i)
    return peaks,env

def autocorr_pitch(x):
    x=x-np.mean(x); rms=np.sqrt(np.mean(x*x)+1e-12)
    if rms<0.008:return None
    x=x*np.hanning(len(x)); ac=np.correlate(x,x,mode="full")[len(x)-1:]
    minlag=max(1,int(SR/1000)); maxlag=min(len(ac)-1,int(SR/55))
    if maxlag<=minlag:return None
    region=ac[minlag:maxlag+1]; lag=minlag+int(np.argmax(region))
    if ac[lag]/(ac[0]+1e-9)<.22:return None
    f=SR/lag; p=float(hz_to_midi(f))
    if 24<=p<=108:return p
    return None

def spectral_peaks(x,max_notes=5):
    win=np.hanning(len(x)); mag=np.abs(np.fft.rfft(x*win))
    freqs=np.fft.rfftfreq(len(x),1/SR)
    mag[:3]=0
    idx=[]
    for i in range(1,len(mag)-1):
        if mag[i]>mag[i-1] and mag[i]>=mag[i+1] and freqs[i]>=55 and freqs[i]<=2000:
            idx.append(i)
    idx=sorted(idx,key=lambda i:mag[i],reverse=True)
    out=[]
    for i in idx:
        p=float(hz_to_midi(freqs[i]))
        if 24<=p<=108 and all(abs(p-q)>0.75 for q,_ in out):
            out.append((p,float(mag[i])))
        if len(out)>=max_notes:break
    return out

def quantize_tick(t,q): return max(0,int(round(t/q)*q))

def extract(y,mode,bpm):
    frames=frame_signal(y); onsets,_=onset_frames(y); onset_set=set(onsets)
    if not onsets:onsets=list(range(0,len(frames),max(1,int(.25*SR/512))))
    # Ensure terminal boundary.
    bounds=onsets+[len(frames)-1]
    notes=[]
    beat_sec=60/bpm; q=max(1,int(TPB/4))
    for j,start in enumerate(onsets):
        end=bounds[j+1] if j+1<len(bounds) else min(len(frames)-1,start+max(2,int(.35*SR/512)))
        a=max(0,start*512); b=min(len(y),(end+1)*512)
        x=y[a:b]
        if len(x)<1024:continue
        rms=float(np.sqrt(np.mean(x*x)+1e-12))
        if rms<.012:continue
        if mode=="percussion":
            spec=np.abs(np.fft.rfft(x*np.hanning(len(x))))
            freqs=np.fft.rfftfreq(len(x),1/SR); centroid=float((freqs*spec).sum()/(spec.sum()+1e-9))
            pitch=36 if centroid<180 else 42 if centroid<700 else 38 if centroid<1800 else 46
            vel=int(np.clip(50+20*np.log10(rms/0.02+1),35,120)); dur=q
            notes.append((pitch,start,dur,vel)); continue
        if mode in ("polyphonic","piano"):
            peaks=spectral_peaks(x,5 if mode=="piano" else 4)
            for p,amp in peaks:
                pitch=int(round(p)); vel=int(np.clip(55+35*amp/(np.max(np.abs(x))*len(x)/8+1e-9),35,120))
                notes.append((pitch,start,q,vel))
        else:
            p=autocorr_pitch(x)
            if p is None:continue
            if mode=="bass" and not 28<=p<=60:continue
            if mode=="vocals" and not 45<=p<=90:continue
            pitch=int(round(p));vel=int(np.clip(65+18*np.log10(rms/0.02+1),35,120));notes.append((pitch,start,q,vel))
    # convert frame positions to ticks and merge repeated pitches
    out=[]; last={}
    for pitch,fr,dur,vel in sorted(notes,key=lambda z:(z[1],z[0])):
        sec=fr*512/SR; tick=quantize_tick(sec/beat_sec*TPB,q)
        key=pitch
        if key in last and tick<=last[key][0]+q:
            continue
        last[key]=(tick,vel);out.append({"pitch":pitch,"start_tick":tick,"duration_ticks":dur,"velocity":vel,"channel":0})
    return out

def write_midi(path,notes,bpm,mode):
    mid=MidiFile(type=1,ticks_per_beat=TPB)
    meta=MidiTrack();meta.append(MetaMessage("track_name",name="Tempo",time=0));meta.append(MetaMessage("set_tempo",tempo=bpm2tempo(bpm),time=0));mid.tracks.append(meta)
    tr=MidiTrack();tr.append(MetaMessage("track_name",name=mode.title(),time=0))
    program={"piano":0,"bass":32,"vocals":52,"percussion":0,"melody":0,"polyphonic":0,"automatic":0}.get(mode,0)
    if mode!="percussion":tr.append(Message("program_change",program=program,channel=0,time=0))
    events=[]
    for n in notes:
        s=n["start_tick"];e=s+n["duration_ticks"];p=n["pitch"];v=n["velocity"]
        ch=9 if mode=="percussion" else 0
        events.extend([(s,1,Message("note_on",note=p,velocity=v,channel=ch)),(e,0,Message("note_off",note=p,velocity=0,channel=ch))])
    events.sort(key=lambda x:(x[0],x[1]));last=0
    for tick,_,msg in events:msg.time=tick-last;tr.append(msg);last=tick
    tr.append(MetaMessage("end_of_track",time=0));mid.tracks.append(tr);mid.save(str(path))

def main():
    ap=argparse.ArgumentParser();ap.add_argument("input");ap.add_argument("output");ap.add_argument("--mode",default="automatic",choices=sorted(MODES));ap.add_argument("--json")
    a=ap.parse_args();mode=a.mode
    y=ffmpeg_pcm(Path(a.input));bpm=estimate_bpm(y)
    if mode=="automatic": mode="polyphonic" if len(spectral_peaks(y[:min(len(y),SR*2)]))>1 else "melody"
    notes=extract(y,mode,bpm);write_midi(Path(a.output),notes,bpm,mode)
    result={"engine":"MIDI Converter Studio Advanced Audio Engine","mode":mode,"bpm":bpm,"sample_rate":SR,"note_count":len(notes),"duration_seconds":round(len(y)/SR,3),"algorithm":["FFmpeg PCM","onset detection","tempo estimation","pitch detection","spectral peak detection" if mode in ("polyphonic","piano") else "autocorrelation pitch tracking","quantization"]}
    if a.json:Path(a.json).write_text(json.dumps(result,indent=2),encoding="utf-8")
    print(json.dumps(result))
if __name__=="__main__":main()
