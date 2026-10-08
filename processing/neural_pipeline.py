#!/usr/bin/env python3
"""PHASE 9 neural pipeline adapter using Demucs + Spotify Basic Pitch."""
from __future__ import annotations
import argparse,json,os,shutil,subprocess,tempfile,sys
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
def merge_midis(items,out):
    import mido
    merged=mido.MidiFile(type=1,ticks_per_beat=480)
    for stem,p in items:
        m=mido.MidiFile(str(p))
        first_note_track=True
        for tr in m.tracks:
            copied=tr.copy()
            if first_note_track and any(getattr(msg,"type",None) in ("note_on","note_off") for msg in copied):
                copied.insert(0,mido.MetaMessage("track_name",name=stem,time=0))
                first_note_track=False
            merged.tracks.append(copied)
    merged.save(str(out))
def main():
    ap=argparse.ArgumentParser();ap.add_argument("input");ap.add_argument("output");ap.add_argument("--mode",default="automatic");ap.add_argument("--model",default=os.getenv("DEMUCS_MODEL","htdemucs"));ap.add_argument("--separate",action="store_true");ap.add_argument("--work-dir")
    a=ap.parse_args();src=Path(a.input).resolve();out=Path(a.output).resolve();work=Path(a.work_dir or tempfile.mkdtemp(prefix="midi-neural-"));work.mkdir(parents=True,exist_ok=True)
    try:
        stems=separate(src,work/"separated",a.model) if a.separate else {"other":src}; mids=[];report={"engine":"phase9-neural","model":a.model,"separated":bool(a.separate),"mode":a.mode,"stems":[]}
        for stem in MODE_TO_STEMS.get(a.mode,["other"]):
            audio=stems.get(stem)
            if not audio: continue
            md=transcribe(audio,work/"midi"/stem);mids.append((stem,md));report["stems"].append({"name":stem,"audio":str(audio),"midi":str(md)})
        if not mids: raise RuntimeError("No suitable stem was available for transcription")
        raw=out.with_name(out.stem+"_raw.mid")
        merge_midis(mids,raw)
        refined=out
        quality_script=Path(__file__).with_name("midi_quality.py")
        quality_cmd=[sys.executable,str(quality_script),str(raw),str(refined),"--mode",a.mode,"--min-velocity",str(a.min_velocity),"--quantize-strength",str(a.quantize_strength),"--track-overrides",a.track_overrides] + (["--no-harmonic-cleanup"] if a.no_harmonic_cleanup else [])
        qr=run(quality_cmd,timeout=3600)
        if qr.returncode!=0:
            raise RuntimeError("PHASE 10 MIDI refinement failed: "+(qr.stderr[-5000:] or qr.stdout[-5000:]))
        quality_report=Path(str(refined)+".json")
        quality=json.loads(quality_report.read_text(encoding="utf-8")) if quality_report.exists() else {}
        raw.unlink(missing_ok=True)
        raw_json=Path(str(raw)+".json"); raw_json.unlink(missing_ok=True)
        report["output"]=str(out)
        report["quality_refinement"]=quality
        Path(str(out)+".json").write_text(json.dumps(report,indent=2),encoding="utf-8")
        print(json.dumps(report))
    finally:
        if not a.work_dir: shutil.rmtree(work,ignore_errors=True)
if __name__=="__main__":main()
