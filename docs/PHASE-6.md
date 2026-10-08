# PHASE 6 — Professional MIDI Editor

## Fitur
- Piano Roll multi-track.
- Add/Delete Note.
- Drag note untuk Move.
- Resize note melalui handle kanan.
- Velocity editor.
- Quantize 1/16, 1/8, 1/4, 1/2.
- Transpose +/- semitone.
- Tempo/BPM 20–300.
- Play/Pause/Stop menggunakan Web Audio API.
- Loop toggle.
- Zoom timeline.
- Mute/Solo track.
- Undo/Redo hingga 50 state.
- Keyboard shortcuts:
  - Delete = hapus note
  - Arrow = move note
  - Ctrl+Z = undo
  - Ctrl+Y = redo
- Save ke database dan rebuild file .mid.
- Download menggunakan authorization file milik user.

## File
- editor.php
- assets/css/midi-editor-pro.css
- assets/js/midi-editor-pro.js
- api/midi/open.php
- api/midi/save.php
- processing/midi_io.py

## Pengujian XAMPP
1. Login sebagai user.
2. Buka file MIDI milik user:
   `editor.php?file_id=ID`
3. Pilih note.
4. Drag note ke waktu/pitch lain.
5. Tarik sisi kanan note untuk mengubah durasi.
6. Ubah velocity.
7. Quantize.
8. Transpose.
9. Ubah BPM.
10. Play/Pause/Stop.
11. Toggle Loop.
12. Mute/Solo track.
13. Undo/Redo.
14. Klik Simpan.
15. Download MIDI.
16. Buka kembali MIDI dan pastikan perubahan tetap ada.

## Validasi server
Browser tidak dipercaya. Endpoint save membatasi:
- pitch 0–127
- velocity 1–127
- channel 0–15
- duration > 0
- start_tick >= 0
- tempo 20–300
- maksimum 64 track
- maksimum 20.000 note per track
- file hanya dapat diedit oleh pemiliknya.

## Catatan playback
Playback editor menggunakan Web Audio API sebagai preview instrumen sederhana. Ini bukan soundfont/GM synthesizer penuh. File MIDI hasil penyimpanan tetap ditulis sebagai MIDI standar oleh processing layer.
