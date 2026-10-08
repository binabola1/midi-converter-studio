<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
require_once dirname(__DIR__,2).'/security.php';
$user=require_login();
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(false,'Method not allowed.',[],405);
verify_csrf($_POST['_csrf']??($_SERVER['HTTP_X_CSRF_TOKEN']??null));
try{
 $fileId=(int)($_POST['file_id']??0);$mode=strtolower(trim((string)($_POST['mode']??'automatic')));
 $allowed=['melody','polyphonic','piano','bass','vocals','percussion','automatic'];
 if(!$fileId||!in_array($mode,$allowed,true))throw new RuntimeException('File atau mode konversi tidak valid.');
 $s=db()->prepare('SELECT * FROM files WHERE id=? AND user_id=? AND type="audio" AND status="active" LIMIT 1');$s->execute([$fileId,$user['id']]);$file=$s->fetch();
 if(!$file)throw new RuntimeException('File audio tidak ditemukan.');
 $lim=plan_limits($user);$s=db()->prepare('SELECT COUNT(*) FROM conversions WHERE user_id=? AND created_at>=DATE_FORMAT(NOW(),"%Y-%m-01") AND status<>"cancelled"');$s->execute([$user['id']]);
 if((int)$s->fetchColumn()>=$lim['monthly_conversions'])throw new RuntimeException('Batas konversi bulanan paket Anda sudah tercapai.');
 $engine=strtolower(trim((string)($_POST['engine']??'neural'))); if(!in_array($engine,['neural','classic'],true))$engine='neural';
 $minVelocity=max(1,min(127,(int)($_POST['min_velocity']??20)));
 $quantizeStrength=max(0,min(1,(float)($_POST['quantize_strength']??0.72)));
 $harmonicCleanup=filter_var($_POST['harmonic_cleanup']??'1',FILTER_VALIDATE_BOOLEAN);
 $trackOverrides=[];
 $rawOverrides=(string)($_POST['track_overrides']??'{}');
 $decoded=json_decode($rawOverrides,true);
 if(is_array($decoded))$trackOverrides=$decoded; else $trackOverrides=[];
 $jobId='job_'.bin2hex(random_bytes(12));$s=db()->prepare('INSERT INTO conversions(user_id,source_file_id,job_id,mode,status,progress) VALUES(?,?,?,?,?,?)');$s->execute([$user['id'],$fileId,$jobId,$mode,'queued',0]);$conversionId=(int)db()->lastInsertId();
 $queue=PROCESSING_ROOT.'/queue/'.$jobId.'.json';file_put_contents($queue,json_encode(['job_id'=>$jobId,'conversion_id'=>$conversionId,'user_id'=>(int)$user['id'],'source_file_id'=>$fileId,'mode'=>$mode,'engine'=>$engine,'quality'=>['min_velocity'=>$minVelocity,'quantize_strength'=>$quantizeStrength,'harmonic_cleanup'=>$harmonicCleanup,'track_overrides'=>$trackOverrides],'created_at'=>date(DATE_ATOM)],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);
 log_activity((int)$user['id'],'conversion_queued','conversion',$conversionId,['job_id'=>$jobId,'mode'=>$mode,'quality'=>['min_velocity'=>$minVelocity,'quantize_strength'=>$quantizeStrength,'harmonic_cleanup'=>$harmonicCleanup]]);
 json_response(true,'Conversion masuk antrean.',['job_id'=>$jobId,'conversion_id'=>$conversionId,'status'=>'queued','progress'=>0,'engine'=>$engine]);
}catch(Throwable $e){json_response(false,$e->getMessage(),[],400);}