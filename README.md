# DocuVault

DocuVault is a Laravel and Filament application for processing uploaded documents with AI extraction and human review.

The planned workflow is:

```text
Upload PDF/Image -> Store Original File -> AI Extraction -> Human Review -> Approved Data
```

Users upload PDFs or images, Laravel stores the original files, a queued job sends the document to `ai.raraxuan.com`, and the AI service returns structured JSON such as extracted fields, confidence scores, ticks, circles, and other detected marks. The result is stored for review in Filament, where a human corrects and approves the data before it becomes searchable or exportable.

AI output is not treated as trusted final data. This project is designed around review-first document processing because real documents can contain handwriting, stamps, marks, messy scans, and low-quality images.

## Current Stack

- PHP 8.4
- Laravel 13
- Filament 5
- Livewire 4
- MariaDB
- Tailwind CSS 4

## Planned Capabilities

- User login and document upload.
- PDF and image storage.
- Background AI processing through queued jobs.
- AI callback handling and raw JSON storage.
- Filament review screens for extracted fields.
- Human correction, approval, rejection, and reprocessing.
- Search, filters, and export of approved data.
- Role permissions, client portal, and audit logs.

## Documentation

Detailed planning docs are in [`docs/`](docs/README.md):

- [Architecture](docs/architecture.md)
- [Data Model](docs/data-model.md)
- [AI Integration](docs/ai-integration.md)
- [Filament Admin](docs/filament-admin.md)
- [Roadmap](docs/roadmap.md)

These docs describe the intended product direction. Some dependencies and features are planned but not installed or implemented yet.
