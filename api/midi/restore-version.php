<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
require_once dirname(__DIR__,2).'/security.php';
$user=require_login();if($_SERVER['REQUEST_METHOD']!=='POST')json_response(false,'Method not allowed.',[],405);
verify_csrf($_POST['_csrf']??($_SERVER['HTTP_X_CSRF_TOKEN']??null));
$id=(int)($_POST['version_id']??0);$s=db()->prepare('SELECT v.* FROM midi_quality_versions v JOIN files f ON f.id=v.file_id WHERE v.id=? AND f.user_id=? LIMIT 1');$s->execute([$id,$user['id']]);$v=$s->fetch();
if(!$v)json_response(false,'Version tidak ditemukan.',[],404);
db()->prepare('UPDATE midi_quality_versions SET is_current=0 WHERE file_id=? OR parent_file_id=?')->execute([(int)$v['file_id'],(int)($v['parent_file_id']?:$v['file_id'])]);
db()->prepare('UPDATE midi_quality_versions SET is_current=1 WHERE id=?')->execute([$id]);
json_response(true,'Version berhasil dijadikan current.',['version_id'=>$id]);
