# PHASE 9 — Neural Audio-to-MIDI + Source Separation

PHASE 9 adds an optional neural processing path:

Audio -> Demucs source separation -> per-stem Basic Pitch transcription -> MIDI merge.

## Models

### Demucs
Demucs is used for music source separation. The supported 4-stem path is:
- vocals
- drums
- bass
- other

The project does not pretend that piano is perfectly isolated by the standard 4-stem model. Piano/instrument transcription uses the other stem unless a future model adapter provides a dedicated piano stem.

### Spotify Basic Pitch
Basic Pitch is used as the neural Automatic Music Transcription (AMT) engine. It supports polyphonic instruments and generates MIDI with pitch-bend information. It works best when the audio contains one instrument/source at a time.

## Mode routing

| Mode | Stem(s) |
|---|---|
| Automatic | vocals + bass + drums + other |
| Vocals | vocals |
| Bass | bass |
| Percussion | drums |
| Piano | other |
| Melody | vocals + other |
| Polyphonic | other + vocals + bass |

## Install

~~~bat
py -m venv .venv
.venv\Scripts\activate
pip install -r processing\requirements.txt
~~~

Demucs downloads model weights on first use. This requires internet access and enough disk space. GPU acceleration is recommended for long files but CPU processing remains available.

## Direct test

~~~bat
python processing\neural_pipeline.py lagu.mp3 hasil.mid --mode automatic --separate
python processing\neural_pipeline.py lagu.mp3 hasil.mid --mode vocals --separate
python processing\neural_pipeline.py lagu.mp3 hasil.mid --mode bass --separate
python processing\neural_pipeline.py lagu.mp3 hasil.mid --mode percussion --separate
python processing\neural_pipeline.py lagu.mp3 hasil.mid --mode piano --separate
python processing\neural_pipeline.py lagu.mp3 hasil.mid --mode polyphonic --separate
~~~

## Queue integration

The conversion API accepts:

~~~text
engine=neural
~~~

or:

~~~text
engine=classic
~~~

The default is neural. The classic PHASE 8 engine remains available as a deterministic fallback.

## Important limitations

1. Source separation is not perfect; bleed and artifacts can remain.
2. The standard Demucs four-stem model does not provide a clean dedicated piano stem.
3. Basic Pitch is not a complete multi-instrument song transcription model; it is strongest on focused/isolated sources.
4. CPU-only machines can take substantially longer and require sufficient RAM/disk.
5. Model weights are not committed into this repository; they are downloaded by the ML package when required.
6. The web application must not report a neural conversion as successful until the actual MIDI file exists.

## Recommended production architecture

~~~text
User Upload
    |
    v
Conversion Queue
    |
    v
Neural Worker
    |
    +--> Demucs separation
    |       +--> vocals.wav
    |       +--> drums.wav
    |       +--> bass.wav
    |       +--> other.wav
    |
    +--> Basic Pitch
    |       +--> vocals.mid
    |       +--> bass.mid
    |       +--> drums.mid
    |       +--> other.mid
    |
    v
MIDI Merge / Track labeling
    |
    v
MIDI Editor
    |
    v
MIDI Studio Player
