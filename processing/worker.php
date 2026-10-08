<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/bootstrap.php';
ensure_directories();

function run_basic_pitch(string $source,string $workDir,string $jobId,string $mode):array{
    $python=getenv('MIDI_PYTHON_BIN') ?: 'python';
    $script=__DIR__.'/convert.py';
    $cmd=escapeshellarg($python).' '.escapeshellarg($script).' '.escapeshellarg($source).' '.escapeshellarg($workDir).' '.escapeshellarg($jobId).' '.escapeshellarg($mode);
    $descriptors=[1=>['pipe','w'],2=>['pipe','w']];
    $p=proc_open($cmd,$descriptors,$pipes,__DIR__);
    if(!is_resource($p))throw new RuntimeException('Python processing engine tidak dapat dijalankan.');
    stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);
    $stderr='';$started=microtime(true);$timeout=defined('PROCESSING_TIMEOUT')?(int)PROCESSING_TIMEOUT:900;
    while(true){
        stream_get_contents($pipes[1]);$stderr.=stream_get_contents($pipes[2]);$status=proc_get_status($p);
        if(!$status['running']){$exit=(int)$status['exitcode'];break;}
        if(microtime(true)-$started>$timeout){proc_terminate($p);fclose($pipes[1]);fclose($pipes[2]);proc_close($p);throw new RuntimeException('Konversi melebihi batas waktu '.$timeout.' detik.');}
        usleep(250000);
    }
    fclose($pipes[1]);fclose($pipes[2]);proc_close($p);
    if($exit!==0)throw new RuntimeException('Basic Pitch gagal: '.substr(trim($stderr),0,3000));
    $midi=$workDir.'/'.$jobId.'.mid';$analysis=$workDir.'/'.$jobId.'.json';
    if(!is_file($midi)||filesize($midi)<32)throw new RuntimeException('Engine selesai tetapi file MIDI tidak valid/tidak ditemukan.');
    if(!is_file($analysis))throw new RuntimeException('Metadata hasil MIDI tidak ditemukan.');
    $meta=json_decode((string)file_get_contents($analysis),true);
    if(!is_array($meta)||!isset($meta['tracks'],$meta['note_count']))throw new RuntimeException('Metadata MIDI tidak valid.');
    return ['midi'=>$midi,'meta'=>$meta];
}

function persist_midi(int $userId,int $conversionId,string $midiPath,array $meta):int{
    ensure_directories();$stored=bin2hex(random_bytes(16)).'.mid';$relative='midi/'.$stored;$target=STORAGE_ROOT.'/'.$relative;
    if(!copy($midiPath,$target))throw new RuntimeException('Gagal menyimpan MIDI hasil konversi.');
    $size=(int)filesize($target);$pdo=db();$pdo->beginTransaction();
    try{
        $s=$pdo->prepare('INSERT INTO files(user_id,type,original_name,stored_name,relative_path,mime_type,extension,size_bytes,duration_seconds,sample_rate,channels,metadata_json) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
        $name=safe_filename((string)($meta['job_id']??'conversion')).'.mid';
        $s->execute([$userId,'midi',$name,$stored,$relative,'audio/midi','mid',$size,$meta['duration_seconds']??null,null,null,json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
        $fileId=(int)$pdo->lastInsertId();
        $trackStmt=$pdo->prepare('INSERT INTO midi_tracks(file_id,track_index,name,channel,instrument,volume,pan) VALUES(?,?,?,?,?,?,?)');
        $noteStmt=$pdo->prepare('INSERT INTO midi_notes(track_id,pitch,start_tick,duration_ticks,velocity,channel) VALUES(?,?,?,?,?,?)');
        foreach(($meta['tracks']??[]) as $track){
            $trackStmt->execute([$fileId,(int)$track['track_index'],$track['name']??null,null,(int)($track['program']??0),100,64]);$trackId=(int)$pdo->lastInsertId();
            foreach(($track['notes']??[]) as $note){$noteStmt->execute([$trackId,(int)$note['pitch'],max(0,(int)$note['start_tick']),max(1,(int)$note['duration_ticks']),max(1,min(127,(int)$note['velocity'])),null]);}
        }
        $s=$pdo->prepare('UPDATE conversions SET output_file_id=?,status="completed",progress=100,processing_engine=?,settings_json=?,completed_at=NOW(),error_message=NULL WHERE id=?');
        $s->execute([$fileId,(string)($meta['engine']??'spotify-basic-pitch-0.4.0'),json_encode(['mode'=>$meta['mode']??'automatic','note_count'=>(int)($meta['note_count']??0)],JSON_UNESCAPED_UNICODE),$conversionId]);
        $pdo->prepare('UPDATE users SET storage_used_bytes=storage_used_bytes+? WHERE id=?')->execute([$size,$userId]);$pdo->commit();
    }catch(Throwable $e){$pdo->rollBack();@unlink($target);throw $e;}
    return $fileId;
}

$items=glob(PROCESSING_ROOT.'/queue/job_*.json')?:[];
foreach($items as $queueFile){
    $job=json_decode((string)file_get_contents($queueFile),true);if(!is_array($job)||empty($job['job_id'])){@unlink($queueFile);continue;}
    $s=db()->prepare('SELECT c.*,f.relative_path FROM conversions c JOIN files f ON f.id=c.source_file_id WHERE c.job_id=? LIMIT 1');$s->execute([$job['job_id']]);$c=$s->fetch();
    if(!$c||$c['status']!=='queued'){@unlink($queueFile);continue;}
    $workDir=PROCESSING_ROOT.'/processing/'.$job['job_id'];if(!is_dir($workDir))mkdir($workDir,0750,true);
    db()->prepare('UPDATE conversions SET status="processing",progress=10,processing_engine="spotify-basic-pitch-0.4.0",started_at=NOW(),error_message=NULL WHERE id=?')->execute([$c['id']]);
    try{
        $source=STORAGE_ROOT.'/'.$c['relative_path'];if(!is_file($source))throw new RuntimeException('Source audio tidak ditemukan.');
        $result=run_basic_pitch($source,$workDir,$job['job_id'],$c['mode']);db()->prepare('UPDATE conversions SET progress=90 WHERE id=?')->execute([$c['id']]);
        persist_midi((int)$c['user_id'],(int)$c['id'],$result['midi'],$result['meta']);
        @rename($queueFile,PROCESSING_ROOT.'/completed/'.$job['job_id'].'.json');@unlink($workDir.'/'.$job['job_id'].'.json');
    }catch(Throwable $e){
        db()->prepare('UPDATE conversions SET status="failed",progress=0,error_message=? WHERE id=?')->execute([substr($e->getMessage(),0,5000),$c['id']]);
        @rename($queueFile,PROCESSING_ROOT.'/failed/'.$job['job_id'].'.json');
    }
}
