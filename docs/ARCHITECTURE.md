# System Architecture
Browser -> PHP Pages/API -> Security/Authorization -> MySQL + Storage -> Conversion Queue -> Processing Adapter -> MIDI -> Editor/Export.

The processing layer supports a local engine or external processing API. It must never report success without a valid MIDI artifact.

API contract: POST /api/v1/convert; GET /api/v1/jobs/{job_id}; GET /api/v1/jobs/{job_id}/result.

FREE: 5 conversions/month, 20 MB/file, 500 MB storage. PRO: 50 conversions/month, 100 MB/file, 5 GB storage. Limits are stored in settings.