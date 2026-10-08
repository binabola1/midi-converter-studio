# PHASE 3 — Upload, FFprobe, File Manager, Queue

## Flow
1. User selects owned audio.
2. Upload API validates extension, MIME, file size and plan storage.
3. File gets a random server filename under uploads/audio.
4. FFprobe extracts codec, duration, bitrate, sample rate and channels.
5. Metadata is stored in the files table.
6. Converter create API creates a unique job_id and JSON queue record.
7. Status API reports the actual DB status.
8. Worker changes queued -> processing and fails safely when no real processing engine is configured.

## Running the worker
From the project root on XAMPP: php processing/worker.php

## Important
PHASE 3 does not claim to convert audio to MIDI. The real engine/API adapter is the next processing phase.