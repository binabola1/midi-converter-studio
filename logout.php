<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/auth.php';
if($_SERVER['REQUEST_METHOD']==='POST') verify_csrf($_POST['_csrf']??null);
logout_user();
redirect('login.php');