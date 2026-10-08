<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
require_once dirname(__DIR__,2).'/includes/auth.php';
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(false,'Method not allowed.',[],405);
try{verify_csrf($_POST['_csrf']??($_SERVER['HTTP_X_CSRF_TOKEN']??null));$u=attempt_login((string)($_POST['email']??''),(string)($_POST['password']??''));json_response(true,'Login berhasil.',['user'=>['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role'],'plan'=>$u['plan']]]);}catch(Throwable $e){json_response(false,$e->getMessage(),[],401);}