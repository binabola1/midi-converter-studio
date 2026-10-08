<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
require_once dirname(__DIR__,2).'/security.php';
$user=require_login();
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(false,'Method not allowed.',[],405);
verify_csrf($_POST['_csrf']??($_SERVER['HTTP_X_CSRF_TOKEN']??null));
try{
$id=(int)($_POST['file_id']??0);$s=db()->prepare('SELECT * FROM files WHERE id=? AND user_id=? AND type="midi" AND status="active" LIMIT 1');$s->execute([$id,$user['id']]);$file=$s->fetch();
if(!$file)throw new RuntimeException('File MIDI tidak ditemukan.');$path=STORAGE_ROOT.'/'.$file['relative_path'];if(!is_file($path))throw new RuntimeException('File MIDI tidak tersedia.');
ensure_directories();$out=STORAGE_ROOT.'/temp/import_'.bin2hex(random_bytes(8)).'.json';$python=getenv('PYTHON_PATH')?:'python';$script=dirname(__DIR__,2).'/processing/midi_io.py';
exec(escapeshellarg($python).' '.escapeshellarg($script).' load '.escapeshellarg($path).' '.escapeshellarg($out),$lines,$code);
if($code!==0||!is_file($out))throw new RuntimeException('Gagal membaca MIDI.');$data=json_decode(file_get_contents($out),true);@unlink($out);if(!is_array($data))throw new RuntimeException('Data MIDI tidak valid.');
$pdo=db();$pdo->beginTransaction();$pdo->prepare('DELETE FROM midi_tracks WHERE file_id=?')->execute([$id]);$ts=$pdo->prepare('INSERT INTO midi_tracks(file_id,track_index,name,channel,instrument) VALUES(?,?,?,?,?)');$ns=$pdo->prepare('INSERT INTO midi_notes(track_id,pitch,start_tick,duration_ticks,velocity,channel) VALUES(?,?,?,?,?,?)');
foreach($data['tracks'] as $t){$ts->execute([$id,$t['track_index'],$t['name'],$t['channel'],$t['program']]);$tid=(int)$pdo->lastInsertId();foreach($t['notes'] as $n)$ns->execute([$tid,$n['pitch'],$n['start_tick'],$n['duration_ticks'],$n['velocity'],$n['channel']]);}
$pdo->prepare('UPDATE files SET metadata_json=? WHERE id=?')->execute([json_encode(['ticks_per_beat'=>(int)$data['ticks_per_beat']],JSON_UNESCAPED_UNICODE),$id]);$pdo->commit();
json_response(true,'MIDI berhasil dimuat ke editor.',['file_id'=>$id,'ticks_per_beat'=>(int)$data['ticks_per_beat'],'tracks'=>count($data['tracks'])]);
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();json_response(false,$e->getMessage(),[],400);}