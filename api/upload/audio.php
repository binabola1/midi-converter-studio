<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
require_once dirname(__DIR__,2).'/security.php';
require_once dirname(__DIR__,2).'/includes/ffprobe.php';
$user=require_login();
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(false,'Method not allowed.',[],405);
verify_csrf($_POST['_csrf']??($_SERVER['HTTP_X_CSRF_TOKEN']??null));
try{
 ensure_directories();
 if(!isset($_FILES['audio']))throw new RuntimeException('File audio belum dipilih.');
 $meta=validate_upload($_FILES['audio'],$user);$limits=plan_limits($user);
 $s=db()->prepare('SELECT COALESCE(SUM(size_bytes),0) FROM files WHERE user_id=? AND status<>?');$s->execute([$user['id'],'deleted']);
 if((int)$s->fetchColumn()+$meta['size']>$limits['storage_mb']*1048576)throw new RuntimeException('Batas penyimpanan paket Anda terlampaui.');
 $stored=bin2hex(random_bytes(16)).'.'.$meta['extension'];$relative='audio/'.$stored;$target=STORAGE_ROOT.'/'.$relative;
 if(!move_uploaded_file($_FILES['audio']['tmp_name'],$target))throw new RuntimeException('Gagal menyimpan file upload.');
 $probe=probe_audio($target);$pdo=db();$pdo->beginTransaction();
 try{$s=$pdo->prepare('INSERT INTO files(user_id,type,original_name,stored_name,relative_path,mime_type,extension,size_bytes,duration_seconds,sample_rate,channels,metadata_json) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');$s->execute([$user['id'],'audio',safe_filename((string)$_FILES['audio']['name']),$stored,$relative,$meta['mime'],$meta['extension'],$meta['size'],$probe['duration'],$probe['sample_rate'],$probe['channels'],json_encode($probe,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);$fileId=(int)$pdo->lastInsertId();$pdo->commit();}catch(Throwable $e){$pdo->rollBack();@unlink($target);throw $e;}
 log_activity((int)$user['id'],'upload','file',$fileId,['original_name'=>$_FILES['audio']['name'],'size'=>$meta['size']]);
 json_response(true,'Audio berhasil diunggah.',['file_id'=>$fileId,'metadata'=>$probe]);
}catch(Throwable $e){json_response(false,$e->getMessage(),[],400);}