# PHASE 8 — Audio-to-MIDI Quality & Advanced Processing

## Pipeline
1. FFmpeg decodes source audio to mono PCM at 22.05 kHz.
2. RMS/onset envelope detects note attacks.
3. Autocorrelation estimates monophonic pitch.
4. Spectral peaks estimate simultaneous pitches for polyphonic/piano modes.
5. Tempo is estimated from onset autocorrelation.
6. Notes are quantized to a 1/16-note grid.
7. Mode-specific filtering creates MIDI notes.
8. A standards-compliant MIDI file is written with tempo metadata.

## Modes

| Mode | Engine |
|---|---|
| Automatic | chooses monophonic/polyphonic analysis from spectral content |
| Melody | monophonic autocorrelation pitch tracking |
| Polyphonic | multiple spectral peaks per onset |
| Piano | higher-density spectral peak extraction |
| Bass | monophonic pitch tracking constrained to bass range |
| Vocals | monophonic pitch tracking constrained to vocal range |
| Percussion | onset + spectral centroid drum mapping |

## Install XAMPP Windows

From the project directory:

~~~bat
py -m venv .venv
.venv\Scripts\activate
pip install -r processing\requirements.txt
~~~

Install FFmpeg and make sure ffmpeg -version works from Command Prompt.

## Run queue worker

~~~bat
.venv\Scripts\python processing\worker.py
~~~

The worker consumes processing/queue/*.json and writes MIDI output to uploads/midi/. Completed analysis reports go to processing/completed/ and failed jobs to processing/failed/.

## Environment variables

~~~text
MIDI_DB_HOST=127.0.0.1
MIDI_DB_PORT=3306
MIDI_DB_USER=root
MIDI_DB_PASS=
MIDI_DB_NAME=midi_converter_studio
MIDI_ENGINE_TIMEOUT=900
~~~

## Quality limitation

This is a deterministic signal-processing engine, not a claim of state-of-the-art source separation. Dense commercial mixes can contain overlapping instruments and vocals; polyphonic spectral peak extraction can produce harmonics or miss notes. A future neural transcription/source-separation adapter can improve this while keeping this engine as the local fallback.

## Direct engine test

~~~bat
python processing\audio_to_midi.py input.mp3 output.mid --mode melody --json report.json
python processing\audio_to_midi.py input.mp3 output.mid --mode polyphonic --json report.json
python processing\audio_to_midi.py input.mp3 output.mid --mode piano --json report.json
python processing\audio_to_midi.py input.mp3 output.mid --mode bass --json report.json
python processing\audio_to_midi.py input.mp3 output.mid --mode vocals --json report.json
python processing\audio_to_midi.py input.mp3 output.mid --mode percussion --json report.json
python processing\audio_to_midi.py input.mp3 output.mid --mode automatic --json report.json
~~~
