<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
$user=require_login();
$type=$_GET['type']??'midi';
if($type!=='midi')$type='midi';
$s=db()->prepare('SELECT id,original_name,size_bytes,created_at,metadata_json FROM files WHERE user_id=? AND type=? AND status="active" ORDER BY created_at DESC LIMIT 200');
$s->execute([$user['id'],$type]);$rows=$s->fetchAll();
json_response(true,'OK',['files'=>array_map(fn($f)=>['id'=>(int)$f['id'],'original_name'=>$f['original_name'],'size_bytes'=>(int)$f['size_bytes'],'created_at'=>$f['created_at']],$rows)]);
