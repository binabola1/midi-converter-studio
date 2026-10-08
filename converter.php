<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';
$user=require_login();
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Converter - <?=e(APP_NAME)?></title></head><body style="font-family:Arial;background:#0b1020;color:#fff;padding:30px"><h1>Audio → MIDI Converter</h1><p>Login aktif sebagai <b><?=e($user['email'])?></b>.</p><p>Upload dan processing engine akan ditambahkan pada PHASE 3. Tidak ada tombol palsu pada fase ini.</p><a href="dashboard.php" style="color:#a5b4fc">← Dashboard</a></body></html>