<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
require_once dirname(__DIR__,2).'/security.php';
$user=require_login();
$id=(int)($_GET['file_id']??0);
$s=db()->prepare('SELECT v.*,f.original_name FROM midi_quality_versions v JOIN files f ON f.id=v.file_id WHERE (v.file_id=? OR v.parent_file_id=?) AND f.user_id=? ORDER BY v.version_number DESC,v.created_at DESC');
$s->execute([$id,$id,$user['id']]);$rows=$s->fetchAll();
json_response(true,'OK',['versions'=>array_map(function($v){return ['id'=>(int)$v['id'],'file_id'=>(int)$v['file_id'],'parent_file_id'=>$v['parent_file_id']?(int)$v['parent_file_id']:null,'version_number'=>(int)$v['version_number'],'label'=>$v['label'],'settings'=>json_decode((string)$v['settings_json'],true)?:[],'quality'=>json_decode((string)$v['quality_json'],true)?:[],'is_current'=>(bool)$v['is_current'],'created_at'=>$v['created_at'],'file_name'=>$v['original_name']];},$rows)]);
