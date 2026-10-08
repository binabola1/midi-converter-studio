<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
$user=require_login();$id=(int)($_GET['id']??0);
$s=db()->prepare('SELECT * FROM files WHERE id=? AND user_id=? AND status="active" LIMIT 1');$s->execute([$id,$user['id']]);$file=$s->fetch();
if(!$file){http_response_code(404);exit('File not found.');}
$path=STORAGE_ROOT.'/'.$file['relative_path'];
if(!is_file($path)){http_response_code(404);exit('Stored file not found.');}
$mime=$file['mime_type']?:'application/octet-stream';$name=safe_filename($file['original_name']);
header('Content-Type: '.$mime);header('Content-Length: '.filesize($path));header('Content-Disposition: attachment; filename="'.$name.'"');header('X-Content-Type-Options: nosniff');readfile($path);exit;
