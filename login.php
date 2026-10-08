<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/auth.php';
if(current_user()) redirect('dashboard.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{verify_csrf($_POST['_csrf']??null);attempt_login((string)$_POST['email'],(string)$_POST['password']);redirect('dashboard.php');}
    catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login - <?=e(APP_NAME)?></title><style>body{font-family:Arial;background:#111827;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0}.card{width:min(420px,92vw);background:#1f2937;padding:32px;border-radius:18px}input{width:100%;box-sizing:border-box;padding:12px;margin:8px 0 16px;border-radius:8px;border:1px solid #4b5563;background:#111827;color:#fff}button{width:100%;padding:12px;border:0;border-radius:8px;background:#6366f1;color:#fff;font-weight:700}.error{background:#7f1d1d;padding:10px;border-radius:8px;margin-bottom:15px}</style></head><body><main class="card"><h1>🎵 <?=e(APP_NAME)?></h1><p>Masuk ke Music Studio Anda.</p><?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><label>Email</label><input name="email" type="email" required autocomplete="email"><label>Password</label><input name="password" type="password" required autocomplete="current-password"><button>Masuk</button></form><p>Belum punya akun? <a href="register.php">Daftar</a></p></main></body></html>