#!/usr/bin/env python3
"""PHASE 9 neural pipeline adapter using Demucs + Spotify Basic Pitch."""
from __future__ import annotations
import argparse,json,os,shutil,subprocess,tempfile
from pathlib import Path
STEMS=("vocals","drums","bass","other")
MODE_TO_STEMS={"vocals":["vocals"],"bass":["bass"],"percussion":["drums"],"piano":["other"],"melody":["vocals","other"],"polyphonic":["other","vocals","bass"],"automatic":["vocals","bass","drums","other"]}
def run(cmd,timeout=3600): return subprocess.run(cmd,capture_output=True,text=True,timeout=timeout)
def separate(src,out_dir,model="htdemucs"):
    out_dir.mkdir(parents=True,exist_ok=True)
    r=run(["python","-m","demucs","-n",model,"-o",str(out_dir),str(src)])
    if r.returncode!=0: raise RuntimeError("Demucs failed: "+(r.stderr[-5000:] or r.stdout[-5000:]))
    root=out_dir/model/Path(src).stem; found={}
    for s in STEMS:
        p=root/(s+".wav")
        if p.exists(): found[s]=p
    if not found: raise RuntimeError("Demucs produced no stems")
    return found
def transcribe(audio,out_dir):
    out_dir.mkdir(parents=True,exist_ok=True)
    r=run(["python","-m","basic_pitch",str(out_dir),str(audio)])
    if r.returncode!=0: r=run(["basic-pitch",str(out_dir),str(audio)])
    if r.returncode!=0: raise RuntimeError("Basic Pitch failed: "+(r.stderr[-5000:] or r.stdout[-5000:]))
    mids=sorted(out_dir.glob("*.mid"))
    if not mids: raise RuntimeError("Basic Pitch produced no MIDI")
    return mids[0]
def merge_midis(paths,out):
    import mido
    merged=mido.MidiFile(type=1,ticks_per_beat=480)
    for p in paths:
        m=mido.MidiFile(str(p))
        for tr in m.tracks: merged.tracks.append(tr.copy())
    merged.save(str(out))
def main():
    ap=argparse.ArgumentParser();ap.add_argument("input");ap.add_argument("output");ap.add_argument("--mode",default="automatic");ap.add_argument("--model",default=os.getenv("DEMucs_MODEL","htdemucs"));ap.add_argument("--separate",action="store_true");ap.add_argument("--work-dir")
    a=ap.parse_args();src=Path(a.input).resolve();out=Path(a.output).resolve();work=Path(a.work_dir or tempfile.mkdtemp(prefix="midi-neural-"));work.mkdir(parents=True,exist_ok=True)
    try:
        stems=separate(src,work/"separated",a.model) if a.separate else {"other":src}; mids=[];report={"engine":"phase9-neural","model":a.model,"separated":bool(a.separate),"mode":a.mode,"stems":[]}
        for stem in MODE_TO_STEMS.get(a.mode,["other"]):
            audio=stems.get(stem)
            if not audio: continue
            md=transcribe(audio,work/"midi"/stem);mids.append(md);report["stems"].append({"name":stem,"audio":str(audio),"midi":str(md)})
        if not mids: raise RuntimeError("No suitable stem was available for transcription")
        merge_midis(mids,out);report["output"]=str(out);Path(str(out)+".json").write_text(json.dumps(report,indent=2),encoding="utf-8");print(json.dumps(report))
    finally:
        if not a.work_dir: shutil.rmtree(work,ignore_errors=True)
if __name__=="__main__":main()
