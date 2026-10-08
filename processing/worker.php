<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/includes/bootstrap.php';
ensure_directories();
$items=glob(PROCESSING_ROOT.'/queue/job_*.json')?:[];
foreach($items as $queueFile){
 $job=json_decode((string)file_get_contents($queueFile),true);if(!is_array($job)||empty($job['job_id'])){@unlink($queueFile);continue;}
 $s=db()->prepare('SELECT c.*,f.relative_path FROM conversions c JOIN files f ON f.id=c.source_file_id WHERE c.job_id=? LIMIT 1');$s->execute([$job['job_id']]);$c=$s->fetch();
 if(!$c||$c['status']!=='queued'){@unlink($queueFile);continue;}
 db()->prepare('UPDATE conversions SET status="processing",progress=1,started_at=NOW() WHERE id=?')->execute([$c['id']]);
 try{
  if(!is_file(STORAGE_ROOT.'/'.$c['relative_path']))throw new RuntimeException('Source audio tidak ditemukan.');
  if(PROCESSING_API_URL==='')throw new RuntimeException('Processing engine belum dikonfigurasi. Atur PROCESSING_API_URL pada config.local.php untuk menjalankan konversi nyata.');
  db()->prepare('UPDATE conversions SET status="failed",progress=0,error_message=? WHERE id=?')->execute(['External processing adapter belum diimplementasikan pada PHASE 3.',$c['id']]);
 }catch(Throwable $e){db()->prepare('UPDATE conversions SET status="failed",progress=0,error_message=? WHERE id=?')->execute([$e->getMessage(),$c['id']]);}
 @rename($queueFile,PROCESSING_ROOT.'/failed/'.$job['job_id'].'.json');
}