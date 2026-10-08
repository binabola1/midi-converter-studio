<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
$user=require_login();
$id=(int)($_GET['file_id']??0);
$s=db()->prepare('SELECT id,original_name,metadata_json FROM files WHERE id=? AND user_id=? AND type="midi" AND status="active" LIMIT 1');
$s->execute([$id,$user['id']]);$file=$s->fetch();
if(!$file)json_response(false,'MIDI tidak ditemukan.',[],404);
$meta=json_decode((string)$file['metadata_json'],true)?:[];
$q=$meta['quality_refinement']??$meta['quality']??[];
if(!$q){
 $base=STORAGE_ROOT.'/'.$file['relative_path'];
 $sidecar=$base.'.json';
 if(is_file($sidecar)){$decoded=json_decode((string)file_get_contents($sidecar),true);if(is_array($decoded))$q=$decoded['quality_refinement']??$decoded;}
}
json_response(true,'OK',['file_id'=>$id,'file_name'=>$file['original_name'],'quality'=>$q]);
