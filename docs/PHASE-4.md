# PHASE 4 — Real MP3/WAV → MIDI Processing Engine

PHASE 4 replaces the PHASE 3 placeholder with a real Automatic Music Transcription (AMT) engine based on Spotify Basic Pitch 0.4.0.

## Why Basic Pitch

Basic Pitch is a lightweight instrument-agnostic AMT model that can generate MIDI from compatible audio and supports polyphonic note transcription. The project documents that its CLI/API can produce a MIDI artifact; the web application therefore validates and stores the actual generated `.mid` rather than renaming an audio file. See the official project documentation: https://github.com/spotify/basic-pitch

## XAMPP / Windows setup

Recommended for this project: Python 3.10 64-bit. Basic Pitch 0.4.0 lists Windows and Python 3.8–3.11 support; Python 3.10 is used here as a conservative Windows runtime choice.

1. Install Python and make sure `python` is available from Command Prompt.
2. Open Command Prompt in the project folder.
3. Create an environment:
   `python -m venv .venv`
4. Activate it:
   Windows CMD: `.venv\\Scripts\\activate`
   PowerShell: `.venv\\Scripts\\Activate.ps1`
5. Install the engine:
   `python -m pip install --upgrade pip`
   `python -m pip install -r processing/requirements.txt`
6. Make sure FFmpeg/FFprobe is installed and `ffprobe` is available on PATH.
7. If Python is not on PATH, set the environment variable `MIDI_PYTHON_BIN` to the full Python executable path.

Example:
`set MIDI_PYTHON_BIN=C:\\path\\to\\midi-converter-studio\\.venv\\Scripts\\python.exe`

## Run the queue worker

After a user uploads an audio file and clicks **Masukkan ke Queue**, run:

`python processing/worker.php`

Correction: the queue worker is PHP, so run it with:

`php processing/worker.php`

The PHP worker invokes `processing/convert.py`, which calls Basic Pitch and writes a real MIDI file plus an analysis JSON sidecar.

## Production hosting

The PHP web application and Python AMT engine can be separated. Set `MIDI_PYTHON_BIN` to the Python executable on the server and use cron/process supervision to run `php processing/worker.php`. On shared hosting where Python execution or long-running processes are prohibited, configure `PROCESSING_API_URL` and implement a trusted external processing adapter instead of attempting to run the model inside a web request.

## Modes

`automatic` and `polyphonic` use the full Basic Pitch frequency range. `melody`, `bass`, `vocals`, and `piano` apply frequency filters. These are processing presets, not source separation: Basic Pitch remains an instrument-agnostic transcription engine and does not magically isolate stems.

## Result persistence

After a successful run the worker:

1. validates the `.mid` artifact;
2. creates a protected MIDI file record;
3. imports track metadata into `midi_tracks`;
4. imports note pitch/start/duration/velocity into `midi_notes`;
5. updates `conversions` to `completed` with 100% progress;
6. exposes the MIDI through the authenticated File Manager download endpoint.

Failures are recorded as `failed` with an error message. No successful status is written unless the MIDI artifact and analysis data exist.
