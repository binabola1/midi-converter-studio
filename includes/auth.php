<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';

function attempt_login(string $email,string $password): array {
    $email=sanitize_email($email);
    $s=db()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
    $s->execute([$email]);
    $user=$s->fetch();
    if(!$user || !password_verify($password,$user['password_hash'])) {
        log_activity($user['id']??null,'login_failed','user',$user['id']??null);
        throw new RuntimeException('Email atau password salah.');
    }
    if($user['status']!=='active') throw new RuntimeException('Akun tidak aktif.');
    session_regenerate_id(true);
    $_SESSION['user_id']=(int)$user['id'];
    $_SESSION['login_at']=time();
    db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$user['id']]);
    log_activity((int)$user['id'],'login','user',(int)$user['id']);
    return $user;
}

function logout_user():void {
    $uid=$_SESSION['user_id']??null;
    if($uid) log_activity((int)$uid,'logout','user',(int)$uid);
    $_SESSION=[];
    if(ini_get('session.use_cookies')) {
        $p=session_get_cookie_params();
        setcookie(session_name(),'',time()-42000,$p['path'],$p['domain']??'',(bool)$p['secure'],(bool)$p['httponly']);
    }
    session_destroy();
}