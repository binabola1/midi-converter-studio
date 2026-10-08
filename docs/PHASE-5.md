# PHASE 5 — MIDI Editor / Piano Roll

Implemented:
- Open an owned MIDI file.
- Normalize MIDI tracks and notes into midi_tracks and midi_notes.
- Piano-roll visualization.
- Select, add and delete notes.
- Change pitch.
- Save edited notes to MySQL.
- Rebuild the physical .mid file with mido.
- Download the saved MIDI.

Setup:
- processing/requirements.txt includes mido.
- Set PYTHON_PATH in config.local.php if Windows does not expose python.

Open editor.php?file_id=123

For a MIDI file that has not been normalized, POST api/midi/import.php with file_id and CSRF token.

Reserved for later phases: note resize handles, quantize, transpose ranges, undo/redo, multi-select, MIDI playback/synth and automation.