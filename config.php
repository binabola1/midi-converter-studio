<?php
declare(strict_types=1);
const APP_NAME='MIDI Converter Studio'; const APP_VERSION='1.0.0'; const APP_ENV='development'; const APP_DEBUG=true; const APP_TIMEZONE='Asia/Jakarta';
const DB_HOST='127.0.0.1'; const DB_PORT='3306'; const DB_NAME='midi_converter_studio'; const DB_USER='root'; const DB_PASS='';
const BASE_URL='http://localhost/midi-converter-studio'; const STORAGE_ROOT=__DIR__.'/uploads'; const PROCESSING_ROOT=__DIR__.'/processing'; const LOG_ROOT=__DIR__.'/logs';
const MAX_UPLOAD_BYTES=104857600;
const ALLOWED_AUDIO_EXTENSIONS=['mp3','wav','m4a','flac','ogg','aac'];
const ALLOWED_AUDIO_MIMES=['audio/mpeg','audio/wav','audio/x-wav','audio/wave','audio/mp4','audio/x-m4a','audio/flac','audio/ogg','audio/aac'];
const PROCESSING_API_URL=''; const PROCESSING_API_KEY=''; const PROCESSING_TIMEOUT=300;
date_default_timezone_set(APP_TIMEZONE);
$local=__DIR__.'/config.local.php'; if(is_file($local)) require $local;
if(session_status()===PHP_SESSION_NONE) session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);