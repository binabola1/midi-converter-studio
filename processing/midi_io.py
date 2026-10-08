#!/usr/bin/env python3
from __future__ import annotations
import json,sys
from pathlib import Path
import mido
def load(path):
    mid=mido.MidiFile(str(path)); tracks=[]
    for ti,tr in enumerate(mid.tracks):
        abs_tick=0; active={}; notes=[]; name=None; channel=0; program=0
        for msg in tr:
            abs_tick+=msg.time
            if msg.type=="track_name": name=msg.name
            if hasattr(msg,"channel"): channel=msg.channel
            if msg.type=="program_change": program=msg.program
            if msg.type=="note_on" and msg.velocity>0: active[(getattr(msg,"channel",0),msg.note)]=(abs_tick,msg.velocity)
            elif msg.type in ("note_off","note_on") and (msg.type=="note_off" or msg.velocity==0):
                key=(getattr(msg,"channel",0),msg.note)
                if key in active:
                    start,vel=active.pop(key); notes.append({"pitch":msg.note,"start_tick":start,"duration_ticks":max(1,abs_tick-start),"velocity":vel,"channel":getattr(msg,"channel",channel)})
        tracks.append({"track_index":ti,"name":name,"channel":channel,"program":program,"notes":notes})
    return {"type":1 if len(mid.tracks)>1 else 0,"ticks_per_beat":mid.ticks_per_beat,"tracks":tracks}
def save(path,data):
    mid=mido.MidiFile(type=int(data.get("type",1)),ticks_per_beat=int(data.get("ticks_per_beat",480)))
    for td in data.get("tracks",[]):
        tr=mido.MidiTrack(); name=td.get("name")
        if name: tr.append(mido.MetaMessage("track_name",name=str(name),time=0))
        events=[]
        for n in td.get("notes",[]):
            p=max(0,min(127,int(n["pitch"]))); st=max(0,int(n["start_tick"])); en=st+max(1,int(n["duration_ticks"])); v=max(1,min(127,int(n.get("velocity",100)))); ch=max(0,min(15,int(n.get("channel",td.get("channel",0)))))
            events += [(st,1,mido.Message("note_on",note=p,velocity=v,channel=ch)),(en,0,mido.Message("note_off",note=p,velocity=0,channel=ch))]
        events.sort(key=lambda x:(x[0],x[1])); last=0
        for tick,_,msg in events: msg.time=tick-last; tr.append(msg); last=tick
        tr.append(mido.MetaMessage("end_of_track",time=0)); mid.tracks.append(tr)
    mid.save(str(path))
if __name__=="__main__":
    if len(sys.argv)!=4: raise SystemExit("usage: midi_io.py load|save INPUT OUTPUT")
    op,inp,out=sys.argv[1:]
    if op=="load": Path(out).write_text(json.dumps(load(inp),ensure_ascii=False),encoding="utf-8")
    elif op=="save": save(out,json.loads(Path(inp).read_text(encoding="utf-8")))
    else: raise SystemExit("unknown operation")