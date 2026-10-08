<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
require_once dirname(__DIR__,2).'/security.php';
$user=require_login();
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(false,'Method not allowed.',[],405);
verify_csrf($_POST['_csrf']??($_SERVER['HTTP_X_CSRF_TOKEN']??null));$jobId=trim((string)($_POST['job_id']??''));
$s=db()->prepare('UPDATE conversions SET status="cancelled",error_message="Cancelled by user" WHERE job_id=? AND user_id=? AND status IN ("queued","processing")');$s->execute([$jobId,$user['id']]);
if(!$s->rowCount())json_response(false,'Job tidak dapat dibatalkan.',[],409);
@unlink(PROCESSING_ROOT.'/queue/'.$jobId.'.json');log_activity((int)$user['id'],'conversion_cancelled','conversion',null,['job_id'=>$jobId]);json_response(true,'Conversion dibatalkan.');