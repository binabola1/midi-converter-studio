#!/usr/bin/env python3
from __future__ import annotations
import json,os,subprocess,sys,traceback
from pathlib import Path
import pymysql
BASE=Path(__file__).resolve().parent.parent
QUEUE=BASE/"processing"/"queue";DONE=BASE/"processing"/"completed";FAILED=BASE/"processing"/"failed";UPLOAD=BASE/"uploads"/"midi"
for p in (QUEUE,DONE,FAILED,UPLOAD):p.mkdir(parents=True,exist_ok=True)
def db():
 return pymysql.connect(host=os.getenv("MIDI_DB_HOST","127.0.0.1"),port=int(os.getenv("MIDI_DB_PORT","3306")),user=os.getenv("MIDI_DB_USER","root"),password=os.getenv("MIDI_DB_PASS",""),database=os.getenv("MIDI_DB_NAME","midi_converter_studio"),autocommit=True,cursorclass=pymysql.cursors.DictCursor)
def progress(cid,p,status=None,error=None):
 with db() as x:
  with x.cursor() as q:q.execute("UPDATE conversions SET progress=%s,status=COALESCE(%s,status),error_message=%s,started_at=COALESCE(started_at,NOW()),completed_at=CASE WHEN %s IN ('completed','failed') THEN NOW() ELSE completed_at END,processing_engine=%s WHERE id=%s",(p,status,error,status,("phase10-neural" if engine=="neural" else "phase8-classic"),cid))
def process(job):
 cid=int(job["conversion_id"]);progress(cid,1,"processing")
 with db() as x:
  with x.cursor() as q:q.execute("SELECT * FROM files WHERE id=%s AND user_id=%s",(job["source_file_id"],job["user_id"]));src=q.fetchone()
 if not src:raise RuntimeError("Source file not found")
 source=BASE/src["relative_path"];out=UPLOAD/(job["job_id"]+".mid");report=DONE/(job["job_id"]+".json")
 if not source.is_file():raise RuntimeError("Source audio missing: "+str(source))
 progress(cid,10)
 engine=job.get("engine","neural")
 if engine=="neural": cmd=[sys.executable,str(BASE/"processing"/"neural_pipeline.py"),str(source),str(out),"--mode",job["mode"],"--separate"]
 else: cmd=[sys.executable,str(BASE/"processing"/"audio_to_midi.py"),str(source),str(out),"--mode",job["mode"],"--json",str(report)]
 proc=subprocess.run(cmd,capture_output=True,text=True,timeout=int(os.getenv("MIDI_ENGINE_TIMEOUT","900")))
 if proc.returncode!=0:raise RuntimeError(proc.stderr[-4000:] or proc.stdout[-4000:] or "engine failed")
 progress(cid,92); meta=json.loads((Path(str(out)+".json") if engine=="neural" and Path(str(out)+".json").exists() else report).read_text(encoding="utf-8")) if (Path(str(out)+".json").exists() if engine=="neural" else report.exists()) else {"engine":engine,"mode":job["mode"]}; size=out.stat().st_size
 with db() as x:
  with x.cursor() as q:
   q.execute("INSERT INTO files(user_id,type,original_name,stored_name,relative_path,mime_type,extension,size_bytes,status,metadata_json) VALUES(%s,'midi',%s,%s,%s,'audio/midi','mid',%s,'active',%s)",(job["user_id"],Path(src["original_name"]).stem+".mid",out.name,"midi/"+out.name,size,json.dumps(meta)))
   fid=q.lastrowid;q.execute("UPDATE conversions SET output_file_id=%s,status='completed',progress=100,processing_engine=%s,settings_json=%s,completed_at=NOW() WHERE id=%s",(fid,"advanced-audio-engine",json.dumps(meta),cid))
def main():
 for f in sorted(QUEUE.glob("*.json")):
  job={}
  try:job=json.loads(f.read_text(encoding="utf-8"));process(job);f.unlink()
  except Exception as e:
   try:progress(int(job["conversion_id"]),0,"failed",str(e))
   except Exception:pass
   (FAILED/f.name).write_text(traceback.format_exc(),encoding="utf-8");f.unlink(missing_ok=True)
if __name__=="__main__":main()
