# Architecture

DocuVault should use Laravel as the main business application, Filament as the admin and client dashboard, queued jobs for background document processing, storage for uploaded originals, MariaDB for reviewed data, and `ai.raraxuan.com` as the external AI/OCR extraction service.

## Current Stack

- PHP 8.4
- Laravel 13
- Filament 5
- Livewire 4
- MariaDB
- Tailwind CSS 4

## Planned Supporting Stack

These dependencies are planned and should be installed only when their phase needs them:

- Redis queue for background processing at production scale.
- Laravel Horizon for queue monitoring and retry visibility.
- S3-compatible storage for private production document storage; local storage is acceptable first.
- Spatie Media Library for richer upload and media handling.
- Spatie Permission for roles and permissions.
- Laravel Scout with Meilisearch when fast document and field search is needed.

## System Responsibilities

- Laravel owns authentication, authorization, document records, review state, exports, and integration with the AI service.
- Filament provides the admin and client dashboard for upload, review, correction, approval, reprocessing, filtering, and export.
- Queue workers run slow or unreliable work outside the request lifecycle, especially AI processing requests.
- Storage keeps original PDFs, images, generated page images, and export files.
- The database stores the clean reviewed state, extracted field history, AI confidence, raw AI JSON, and reviewer notes.
- `ai.raraxuan.com` performs OCR, mark detection, and structured extraction.

## Main Workflow

1. User logs in.
2. User uploads a PDF or image.
3. Laravel stores the original file.
4. Laravel creates a document record with `uploaded` status.
5. Laravel dispatches a queued processing job.
6. The job marks the document as `processing` and sends the file to `ai.raraxuan.com`.
7. The AI service calls back with structured JSON.
8. Laravel validates the callback and stores the raw AI result.
9. Laravel creates extracted field records and marks the document as `needs_review`.
10. A human reviewer corrects and approves or rejects fields in Filament.
11. Approved data becomes searchable and exportable.

## Review Rule

AI output is never trusted as final data. Documents may include handwriting, circles, ticks, stamps, messy scans, and low-quality images.

The required lifecycle is:

```text
Uploaded Document -> AI Extraction -> Human Review -> Approved Data
```

Search, exports, reports, and downstream workflows must use reviewed and approved values, not raw AI output.
