<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
$user=require_login();$jobId=trim((string)($_GET['job_id']??''));
if($jobId==='')json_response(false,'job_id wajib diisi.',[],400);
$s=db()->prepare('SELECT c.*,f.original_name source_name,o.original_name output_name FROM conversions c JOIN files f ON f.id=c.source_file_id LEFT JOIN files o ON o.id=c.output_file_id WHERE c.job_id=? AND c.user_id=? LIMIT 1');$s->execute([$jobId,$user['id']]);$r=$s->fetch();
if(!$r)json_response(false,'Job tidak ditemukan.',[],404);
json_response(true,'OK',['job'=>['id'=>(int)$r['id'],'job_id'=>$r['job_id'],'source_name'=>$r['source_name'],'output_name'=>$r['output_name'],'mode'=>$r['mode'],'status'=>$r['status'],'progress'=>(int)$r['progress'],'engine'=>$r['processing_engine'],'error'=>$r['error_message'],'created_at'=>$r['created_at'],'started_at'=>$r['started_at'],'completed_at'=>$r['completed_at']]]);