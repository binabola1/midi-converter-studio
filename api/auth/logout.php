<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
require_once dirname(__DIR__,2).'/includes/auth.php';
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(false,'Method not allowed.',[],405);
verify_csrf($_POST['_csrf']??($_SERVER['HTTP_X_CSRF_TOKEN']??null));logout_user();json_response(true,'Logout berhasil.');