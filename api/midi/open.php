<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
$user=require_login();$id=(int)($_GET['file_id']??0);$s=db()->prepare('SELECT * FROM files WHERE id=? AND user_id=? AND type="midi" AND status="active" LIMIT 1');$s->execute([$id,$user['id']]);$file=$s->fetch();
if(!$file)json_response(false,'MIDI tidak ditemukan.',[],404);
$tracks=db()->prepare('SELECT * FROM midi_tracks WHERE file_id=? ORDER BY track_index');$tracks->execute([$id]);$out=[];
foreach($tracks->fetchAll() as $t){$n=db()->prepare('SELECT id,pitch,start_tick,duration_ticks,velocity,channel FROM midi_notes WHERE track_id=? ORDER BY start_tick,id');$n->execute([$t['id']]);$t['notes']=$n->fetchAll();$out[]=$t;}
$meta=json_decode((string)$file['metadata_json'],true)?:[];json_response(true,'OK',['file'=>['id'=>(int)$file['id'],'name'=>$file['original_name']],'ticks_per_beat'=>(int)($meta['ticks_per_beat']??480),'tracks'=>$out]);