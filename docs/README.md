# DocuVault Documentation

DocuVault is planned as a Laravel 13 and Filament 5 document processing app. Users upload PDFs or images, the app sends them to `ai.raraxuan.com` for OCR and AI extraction, and reviewers approve corrected data before it becomes searchable or exportable.

Read these docs in order:

1. [Architecture](architecture.md) - system boundaries, workflow, and current vs planned stack.
2. [Data Model](data-model.md) - planned document, page, field, and note records.
3. [AI Integration](ai-integration.md) - outbound processing request and callback contract.
4. [Filament Admin](filament-admin.md) - planned resources and review screens.
5. [Roadmap](roadmap.md) - phased MVP implementation plan and acceptance criteria.

These docs describe the intended product direction. They do not mean every package, table, resource, or workflow has already been implemented.
