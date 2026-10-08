<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
require_once dirname(__DIR__,2).'/security.php';
$user=require_login();
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(false,'Method not allowed.',[],405);
verify_csrf($_POST['_csrf']??($_SERVER['HTTP_X_CSRF_TOKEN']??null));
try{
 $fileId=(int)($_POST['file_id']??0);if(!$fileId)throw new RuntimeException('MIDI file tidak valid.');
 $s=db()->prepare('SELECT * FROM files WHERE id=? AND user_id=? AND type="midi" AND status="active" LIMIT 1');$s->execute([$fileId,$user['id']]);$file=$s->fetch();
 if(!$file)throw new RuntimeException('MIDI tidak ditemukan.');
 $min=max(1,min(127,(int)($_POST['min_velocity']??20)));$qs=max(0,min(1,(float)($_POST['quantize_strength']??.72)));$hc=filter_var($_POST['harmonic_cleanup']??'1',FILTER_VALIDATE_BOOLEAN);
 $ov=json_decode((string)($_POST['track_overrides']??'{}'),true);if(!is_array($ov))$ov=[];
 $mode='automatic';$job='job_'.bin2hex(random_bytes(12));
 $q=db()->prepare('INSERT INTO conversions(user_id,source_file_id,job_id,mode,status,progress,processing_engine) VALUES(?,?,?,?,?,?,?)');$q->execute([$user['id'],$fileId,$job,$mode,'queued',0,'phase12-reprocess']);$cid=(int)db()->lastInsertId();
 $payload=['job_id'=>$job,'conversion_id'=>$cid,'user_id'=>(int)$user['id'],'source_file_id'=>$fileId,'parent_file_id'=>$fileId,'mode'=>$mode,'engine'=>'reprocess','quality'=>['min_velocity'=>$min,'quantize_strength'=>$qs,'harmonic_cleanup'=>$hc,'track_overrides'=>$ov],'created_at'=>date(DATE_ATOM)];
 file_put_contents(PROCESSING_ROOT.'/queue/'.$job.'.json',json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);
 json_response(true,'Re-process masuk antrean.',['job_id'=>$job,'conversion_id'=>$cid,'status'=>'queued']);
}catch(Throwable $e){json_response(false,$e->getMessage(),[],400);}
