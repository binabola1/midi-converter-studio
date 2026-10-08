<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/bootstrap.php';
require_once dirname(__DIR__,2).'/security.php';
$user=require_login();if($_SERVER['REQUEST_METHOD']!=='POST')json_response(false,'Method not allowed.',[],405);verify_csrf($_POST['_csrf']??($_SERVER['HTTP_X_CSRF_TOKEN']??null));
try{
$id=(int)($_POST['file_id']??0);$payload=json_decode((string)($_POST['midi_json']??''),true);
if(!$id||!is_array($payload)||!isset($payload['tracks'])||count($payload['tracks'])>64)throw new RuntimeException('Data MIDI tidak valid.');
$s=db()->prepare('SELECT * FROM files WHERE id=? AND user_id=? AND type="midi" AND status="active" LIMIT 1');$s->execute([$id,$user['id']]);$file=$s->fetch();if(!$file)throw new RuntimeException('MIDI tidak ditemukan.');
foreach($payload['tracks'] as &$t){if(!isset($t['notes'])||!is_array($t['notes']))throw new RuntimeException('Track notes tidak valid.');if(count($t['notes'])>20000)throw new RuntimeException('Jumlah not terlalu banyak.');foreach($t['notes'] as &$n){$n['pitch']=max(0,min(127,(int)$n['pitch']));$n['start_tick']=max(0,(int)$n['start_tick']);$n['duration_ticks']=max(1,min(10000000,(int)$n['duration_ticks']));$n['velocity']=max(1,min(127,(int)$n['velocity']));$n['channel']=max(0,min(15,(int)($n['channel']??0)));}unset($n);}unset($t);
$pdo=db();$pdo->beginTransaction();$pdo->prepare('DELETE FROM midi_tracks WHERE file_id=?')->execute([$id]);$ts=$pdo->prepare('INSERT INTO midi_tracks(file_id,track_index,name,channel,instrument) VALUES(?,?,?,?,?)');$ns=$pdo->prepare('INSERT INTO midi_notes(track_id,pitch,start_tick,duration_ticks,velocity,channel) VALUES(?,?,?,?,?,?)');
foreach($payload['tracks'] as $t){$ts->execute([$id,(int)$t['track_index'],substr((string)($t['name']??''),0,190),(int)($t['channel']??0),(int)($t['instrument']??0)]);$tid=(int)$pdo->lastInsertId();foreach($t['notes'] as $n)$ns->execute([$tid,$n['pitch'],$n['start_tick'],$n['duration_ticks'],$n['velocity'],$n['channel']]);}
$tmp=STORAGE_ROOT.'/temp/save_'.bin2hex(random_bytes(8)).'.json';$tmpOut=STORAGE_ROOT.'/temp/midi_'.bin2hex(random_bytes(8)).'.mid';file_put_contents($tmp,json_encode(['type'=>1,'ticks_per_beat'=>(int)($payload['ticks_per_beat']??480),'tracks'=>$payload['tracks']],JSON_UNESCAPED_UNICODE));
$python=getenv('PYTHON_PATH')?:'python';$script=dirname(__DIR__,2).'/processing/midi_io.py';exec(escapeshellarg($python).' '.escapeshellarg($script).' save '.escapeshellarg($tmp).' '.escapeshellarg($tmpOut),$lines,$code);
if($code!==0||!is_file($tmpOut)||filesize($tmpOut)<32){$pdo->rollBack();@unlink($tmp);@unlink($tmpOut);throw new RuntimeException('Gagal menulis file MIDI.');}
$stored=bin2hex(random_bytes(16)).'.mid';$relative='midi/'.$stored;if(!rename($tmpOut,STORAGE_ROOT.'/'.$relative)){$pdo->rollBack();throw new RuntimeException('Gagal menyimpan MIDI.');}
$pdo->prepare('UPDATE files SET stored_name=?,relative_path=?,size_bytes=?,metadata_json=? WHERE id=?')->execute([$stored,$relative,filesize(STORAGE_ROOT.'/'.$relative),json_encode(['ticks_per_beat'=>(int)($payload['ticks_per_beat']??480)],JSON_UNESCAPED_UNICODE),$id]);$pdo->commit();@unlink($tmp);log_activity((int)$user['id'],'midi_saved','file',$id);json_response(true,'MIDI berhasil disimpan.',['file_id'=>$id]);
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();json_response(false,$e->getMessage(),[],400);}