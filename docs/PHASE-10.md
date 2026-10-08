# PHASE 10 — Multi-Stem MIDI Intelligence & Quality Refinement

PHASE 10 is the post-processing stage after Demucs + Basic Pitch. It converts raw neural transcription into cleaner, more musical MIDI before the editor receives it.

## Pipeline

```text
Audio
  -> Demucs stems
  -> Basic Pitch per stem
  -> raw MIDI merge
  -> PHASE 10 MIDI intelligence
       -> track labeling
       -> instrument/program classification
       -> low-confidence filtering
       -> duplicate/unison cleanup
       -> harmonic near-duplicate cleanup
       -> adjacent-note merging
       -> velocity normalization
       -> tempo-map preservation
       -> adaptive quantization
       -> MIDI reconstruction/cleanup
  -> final MIDI
  -> MIDI Editor
```

## Implemented quality operations

### 1. Track labeling
Tracks are renamed using the detected/source stem context:
- Vocals
- Drums
- Bass
- Piano
- Melody
- Instrument

A numbered suffix is used when multiple tracks share a type.

### 2. Instrument classification
A conservative GM program is assigned:
- Vocals -> Voice Oohs
- Bass -> Electric Bass (finger)
- Piano -> Acoustic Grand Piano
- Instrument -> String Ensemble 1
- Drums -> GM percussion channel 10

The classification is intentionally conservative; PHASE 10 does not claim to identify every instrument in a mixed recording.

### 3. Note confidence filtering
Basic Pitch MIDI does not provide a portable confidence field in the generated MIDI. Therefore PHASE 10 uses the source MIDI note velocity as a documented confidence proxy.

Default:
`min_velocity = 20`

This can be changed from the command line.

### 4. Duplicate and harmonic cleanup
Same-pitch notes occurring at nearly the same onset are collapsed, retaining the stronger event. Optional near-unison cleanup removes overlapping pitches one semitone apart when the weaker event is likely a transcription artifact.

### 5. Note merging
Adjacent notes of the same pitch/channel with a small gap are merged to reduce choppy artifacts.

### 6. Velocity normalization
Remaining notes are normalized into a practical musical range while preserving relative dynamics.

### 7. Tempo alignment
The original MIDI tempo map is preserved when available. If the raw MIDI contains no tempo event, 120 BPM is used as a safe fallback.

### 8. Adaptive quantization
Quantization is note-length aware:
- very short notes -> 1/16 grid
- short notes -> 1/8
- medium notes -> 1/4
- long notes -> 1/2

Quantization uses partial strength (default 72%) rather than snapping every note completely, which reduces robotic timing.

### 9. Automatic MIDI cleanup
The final MIDI is rebuilt with:
- clean delta times
- explicit note-off events
- tempo/master track
- GM program/channel assignment
- empty-track removal
- final JSON quality report

## Direct test

```bat
python processing/midi_quality.py raw.mid refined.mid --mode automatic
```

More conservative cleanup:

```bat
python processing/midi_quality.py raw.mid refined.mid --mode automatic --min-velocity 30 --quantize-strength 0.55
```

Disable harmonic cleanup:

```bat
python processing/midi_quality.py raw.mid refined.mid --no-harmonic-cleanup
```

## Neural pipeline

PHASE 9 now calls PHASE 10 automatically:

```text
neural_pipeline.py
  -> Demucs
  -> Basic Pitch
  -> raw MIDI merge
  -> midi_quality.py
  -> final .mid
```

The quality report is stored alongside the final MIDI as `<file>.mid.json` and included in the conversion metadata.

## Important accuracy boundary

PHASE 10 improves the structure and playability of transcription; it does not magically recover information that was never detected by source separation or Basic Pitch. It is deliberately conservative so that cleanup does not become a false claim of perfect transcription.

## Next recommended phase

PHASE 11 can expose these quality controls in the web UI:
- confidence threshold
- quantization strength
- harmonic cleanup toggle
- instrument/track override
- quality report
- before/after note statistics
- re-process button
