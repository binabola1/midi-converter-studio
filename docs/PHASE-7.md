# PHASE 7 — MIDI Studio Player

PHASE 7 menambahkan player studio di atas MIDI editor.

## Fitur
- General MIDI instrument selector.
- Web Audio MIDI synth preview dengan 128 GM program names.
- Virtual piano keyboard 88-key style.
- HTML5 audio player untuk audio source.
- Audio seek, volume, current time dan duration.
- MIDI/audio synchronization timeline.
- Real-time playhead pada piano roll.
- Per-track volume dan pan.
- Mute/Solo.
- Metronome.
- BPM-aware playback.
- Loop.
- Space = play/pause.
- M = metronome.
- S = stop.
- Piano keyboard mouse/touch/click.
- MIDI note preview melalui Web Audio API.

## SoundFont
PHASE 7 menggunakan SoundFont-compatible architecture tetapi browser fallback memakai Web Audio synthesis agar aplikasi tetap berjalan tanpa file SoundFont eksternal.

Untuk kualitas instrumen SoundFont produksi:
1. Letakkan SoundFont yang penggunaannya legal di assets/soundfonts/.
2. Integrasikan SoundFont player/library pada adapter client.
3. Jangan memasukkan SoundFont berhak cipta tanpa izin.

## Audio synchronization
HTML5 audio menjadi clock ketika audio source tersedia. MIDI preview mengikuti currentTime audio. Jika audio tidak tersedia, MIDI clock menggunakan BPM.

## Server
Player tidak membuat file MIDI baru saat playback. Save tetap menggunakan api/midi/save.php dan processing/midi_io.py.

## Testing
- Open editor.php?file_id=ID
- Pilih instrument.
- Tekan piano virtual.
- Play MIDI.
- Ubah volume/pan.
- Mute/Solo track.
- Aktifkan metronome.
- Jika file audio tersedia, Play Audio dan pastikan playhead mengikuti currentTime.
- Seek audio dan pastikan playhead berpindah.
- Save dan download MIDI.
