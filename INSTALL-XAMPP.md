# XAMPP Installation
1. Put the project in C:\xampp\htdocs\midi-converter-studio.
2. Start Apache and MySQL.
3. Import database/database.sql in phpMyAdmin.
4. Configure DB constants in config.php or create config.local.php.
5. Ensure uploads/*, processing/* and logs are writable.
6. Install FFmpeg/FFprobe and verify with ffmpeg -version and ffprobe -version.
7. Open http://localhost/midi-converter-studio/.

Do not commit production passwords. PHASE 1 intentionally does not fake audio-to-MIDI conversion; the real processing adapter belongs to the next phase.