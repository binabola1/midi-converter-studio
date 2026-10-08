#!/usr/bin/env python3
import argparse,json,subprocess,sys
from pathlib import Path
from midi_quality import refine

def main():
 p=argparse.ArgumentParser()
 p.add_argument("input");p.add_argument("output")
 p.add_argument("--mode",default="automatic")
 p.add_argument("--min-velocity",type=int,default=20)
 p.add_argument("--quantize-strength",type=float,default=.72)
 p.add_argument("--no-harmonic-cleanup",action="store_true")
 p.add_argument("--track-overrides",default="{}")
 a=p.parse_args()
 try: overrides=json.loads(a.track_overrides)
 except Exception: raise SystemExit("Invalid track override JSON")
 report=refine(a.input,a.output,a.mode,max(1,min(127,a.min_velocity)),max(0,min(1,a.quantize_strength)),not a.no_harmonic_cleanup,overrides)
 print(json.dumps(report))
if __name__=="__main__": main()
