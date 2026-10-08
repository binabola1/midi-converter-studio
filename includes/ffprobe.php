<?php
declare(strict_types=1);
require_once __DIR__.'/../config.php';
function probe_audio(string $absolutePath):array{
 if(!is_file($absolutePath))throw new RuntimeException('File audio tidak ditemukan.');
 $ffprobe=getenv('FFPROBE_PATH')?:'ffprobe';$cmd=escapeshellarg($ffprobe).' -v error -show_entries format=duration,bit_rate:stream=codec_name,sample_rate,channels -of json '.escapeshellarg($absolutePath);
 $p=proc_open($cmd,[1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($p))throw new RuntimeException('FFprobe tidak dapat dijalankan.');
 $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($p);
 if($code!==0)throw new RuntimeException('FFprobe gagal: '.trim($err));$data=json_decode($out,true);if(!is_array($data))throw new RuntimeException('Output FFprobe tidak valid.');
 $stream=$data['streams'][0]??[];$format=$data['format']??[];
 return ['codec'=>$stream['codec_name']??null,'duration'=>isset($format['duration'])?(float)$format['duration']:null,'bit_rate'=>isset($format['bit_rate'])?(int)$format['bit_rate']:null,'sample_rate'=>isset($stream['sample_rate'])?(int)$stream['sample_rate']:null,'channels'=>isset($stream['channels'])?(int)$stream['channels']:null];
}