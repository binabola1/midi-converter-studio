<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';
if(current_user()) redirect('dashboard.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  verify_csrf($_POST['_csrf']??null);
  $name=trim((string)$_POST['name']); $email=sanitize_email((string)$_POST['email']); $password=(string)$_POST['password'];
  if(mb_strlen($name)<2||mb_strlen($name)>120) throw new RuntimeException('Nama harus 2-120 karakter.');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email tidak valid.');
  if(!validate_password($password)) throw new RuntimeException('Password minimal 8 karakter dan harus memiliki huruf besar, huruf kecil, serta angka.');
  $s=db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');$s->execute([$email]);if($s->fetch())throw new RuntimeException('Email sudah terdaftar.');
  $s=db()->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)');$s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
  log_activity((int)db()->lastInsertId(),'register','user',(int)db()->lastInsertId()); redirect('login.php');
 }catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Daftar - <?=e(APP_NAME)?></title><style>body{font-family:Arial;background:#0f172a;color:#fff;display:grid;place-items:center;min-height:100vh}.card{width:min(460px,92vw);background:#1e293b;padding:32px;border-radius:18px}input{width:100%;box-sizing:border-box;padding:12px;margin:8px 0 16px;border-radius:8px;border:1px solid #475569;background:#0f172a;color:#fff}button{width:100%;padding:12px;border:0;border-radius:8px;background:#8b5cf6;color:white;font-weight:bold}.error{background:#7f1d1d;padding:10px;border-radius:8px}</style></head><body><main class="card"><h1>Buat Akun</h1><?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><label>Nama</label><input name="name" required maxlength="120"><label>Email</label><input name="email" type="email" required><label>Password</label><input name="password" type="password" minlength="8" required><button>Daftar</button></form><p>Sudah punya akun? <a href="login.php">Login</a></p></main></body></html>