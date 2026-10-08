#!/usr/bin/env python3
"""Real audio-to-MIDI worker using Spotify Basic Pitch."""
from __future__ import annotations
import json
import sys
import traceback
from pathlib import Path
from basic_pitch.inference import predict
from basic_pitch import ICASSP_2022_MODEL_PATH
try:
    import pretty_midi
except Exception as exc:
    raise RuntimeError("pretty_midi is required by basic-pitch") from exc

def mode_limits(mode: str):
    # These are frequency filters, not instrument-separation claims.
    return {
        "bass": (27.5, 261.63), "vocals": (80.0, 1200.0),
        "piano": (27.5, 4186.01), "melody": (55.0, 1760.0),
        "polyphonic": (None, None), "automatic": (None, None),
        "percussion": (None, None),
    }.get(mode, (None, None))

def main() -> int:
    if len(sys.argv) != 5:
        print("usage: convert.py INPUT_AUDIO OUTPUT_DIR JOB_ID MODE", file=sys.stderr); return 2
    audio=Path(sys.argv[1]).resolve(); output_dir=Path(sys.argv[2]).resolve(); job_id=sys.argv[3]; mode=sys.argv[4].lower()
    if not audio.is_file(): raise RuntimeError(f"Input audio not found: {audio}")
    output_dir.mkdir(parents=True,exist_ok=True)
    minimum_frequency,maximum_frequency=mode_limits(mode)
    print(json.dumps({"event":"started","job_id":job_id,"mode":mode}),flush=True)
    _, midi_data, _ = predict(audio,ICASSP_2022_MODEL_PATH,minimum_frequency=minimum_frequency,maximum_frequency=maximum_frequency,melodia_trick=True,multiple_pitch_bends=False)
    midi_path=output_dir/f"{job_id}.mid"; analysis_path=output_dir/f"{job_id}.json"
    midi_data.write(str(midi_path))
    if not midi_path.is_file() or midi_path.stat().st_size < 32: raise RuntimeError("Basic Pitch did not produce a valid MIDI artifact.")
    midi=pretty_midi.PrettyMIDI(str(midi_path)); tracks=[]; total_notes=0
    for index,instrument in enumerate(midi.instruments):
        notes=[]
        for note in instrument.notes:
            start_tick=int(round(midi.time_to_tick(note.start))); end_tick=int(round(midi.time_to_tick(note.end)))
            notes.append({"pitch":int(note.pitch),"start":float(note.start),"end":float(note.end),"start_tick":start_tick,"duration_ticks":max(1,end_tick-start_tick),"velocity":int(note.velocity)})
        total_notes+=len(notes)
        tracks.append({"track_index":index,"name":instrument.name or None,"program":int(instrument.program),"is_drum":bool(instrument.is_drum),"notes":notes})
    try: tempo=float(midi.estimate_tempo()) if midi.get_end_time()>0 else 120.0
    except Exception: tempo=120.0
    analysis={"job_id":job_id,"engine":"spotify-basic-pitch-0.4.0","mode":mode,"midi_file":str(midi_path),"duration_seconds":float(midi.get_end_time()),"resolution":int(midi.resolution),"tempo_bpm":tempo,"track_count":len(tracks),"note_count":total_notes,"tracks":tracks}
    analysis_path.write_text(json.dumps(analysis,ensure_ascii=False),encoding="utf-8")
    print(json.dumps({"event":"completed","midi":str(midi_path),"analysis":str(analysis_path),"notes":total_notes}),flush=True)
    return 0

if __name__=="__main__":
    try: raise SystemExit(main())
    except Exception as exc:
        print(json.dumps({"event":"failed","error":str(exc),"traceback":traceback.format_exc()}),file=sys.stderr,flush=True); raise
