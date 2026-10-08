# PHASE 12 — Professional MIDI Quality & Reprocessing Dashboard

PHASE 12 turns the PHASE 10/11 quality engine into a versioned production workflow.

## Dashboard

Page:

`quality-dashboard.php`

Features:
- MIDI selector
- confidence threshold slider
- quantization strength slider
- harmonic/duplicate cleanup toggle
- track/instrument override JSON
- re-process button
- job progress
- A/B quality statistics
- quality score
- full quality JSON report
- version history
- restore current version

## Processing

A re-process request creates a new queue job with:

```text
engine = reprocess
parent_file_id = selected MIDI
quality = {
  min_velocity,
  quantize_strength,
  harmonic_cleanup,
  track_overrides
}
```

The worker calls:

`processing/reprocess_midi.py`

which runs the same deterministic PHASE 10 refiner against the existing MIDI. The original MIDI is not overwritten.

## Versioning

New table:

`midi_quality_versions`

Each re-process result stores:
- file_id
- parent_file_id
- version number
- label
- quality settings
- quality report
- current flag
- timestamp

This permits repeated experimentation without destroying earlier MIDI outputs.

## Restore

`api/midi/restore-version.php`

Restore means selecting an existing version as the current version; the underlying files remain intact.

## APIs

- `api/midi/reprocess.php`
- `api/midi/versions.php`
- `api/midi/restore-version.php`
- `api/midi/quality.php`

## Quality Score

The dashboard calculates a transparent heuristic score from actual report data. It is not an AI accuracy percentage. It balances note retention with useful cleanup activity and is intended for comparing different refinement settings.

## Database migration

For an existing installation, execute the PHASE 12 `CREATE TABLE IF NOT EXISTS midi_quality_versions` statement from `database/database.sql`.

For a fresh installation, the table is included automatically.

## Recommended workflow

1. Convert audio with PHASE 9 neural transcription.
2. Open Quality Dashboard.
3. Review the quality report.
4. Adjust threshold/quantization.
5. Re-process.
6. Compare statistics.
7. Keep the best version.
8. Restore a preferred version when necessary.

## Important accuracy boundary

The quality score is a refinement/comparison metric, not a transcription accuracy measurement. PHASE 12 still uses source MIDI velocity as the confidence proxy because the portable MIDI output does not expose a native Basic Pitch confidence field.
