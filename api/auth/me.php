<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
$u=current_user();if(!$u)json_response(false,'Not authenticated.',[],401);
json_response(true,'Authenticated.',['user'=>['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role'],'plan'=>$u['plan'],'status'=>$u['status']]]);