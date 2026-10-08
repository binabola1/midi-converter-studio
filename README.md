# MIDI Converter Studio

Professional web application foundation for audio-to-MIDI conversion.

## PHASE 1 completed

- PHP 8.2+ architecture
- MySQL/MariaDB schema
- PDO prepared statements
- Secure session/cookie baseline
- CSRF and output escaping helpers
- Upload MIME/extension/size validation
- User, admin, plan and usage data model
- Conversion queue data model
- MIDI tracks and notes data model
- Activity logging
- Processing API configuration placeholders
- XAMPP installation guide
- Storage upload execution protection
- System architecture documentation

## Planned PHASE 2+

Authentication, registration, upload UI, FFprobe metadata extraction, conversion queue/API adapter, real audio-to-MIDI processing, MIDI parser/editor, dashboard, file manager, admin panel, installer and production deployment hardening.

## Important

This project does not generate fake MIDI files, fake progress, or rename audio files as MIDI. A conversion is only successful when a real processing engine produces a valid MIDI artifact.

## License

Choose and add the production license before public distribution.