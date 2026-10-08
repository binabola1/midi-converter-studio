<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
$user=require_login();$id=(int)($_GET['id']??0);
$s=db()->prepare('SELECT * FROM files WHERE id=? AND user_id=? AND status="active" LIMIT 1');$s->execute([$id,$user['id']]);$f=$s->fetch();
if(!$f){http_response_code(404);exit('File not found.');}
$p=STORAGE_ROOT.'/'.$f['relative_path'];if(!is_file($p)){http_response_code(404);exit('Stored file not found.');}
header('Content-Type: '.($f['mime_type']?:'application/octet-stream'));header('Content-Length: '.filesize($p));header('Content-Disposition:inline; filename="'.safe_filename($f['original_name']).'"');header('X-Content-Type-Options:nosniff');readfile($p);