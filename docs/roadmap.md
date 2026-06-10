# Roadmap

This roadmap turns the document processing plan into implementation phases. Each phase should leave the app in a usable state before moving to the next one.

## Phase 1: Login, Upload, and Document List

Build the basic authenticated document library.

Planned work:

- Configure Filament login and access.
- Create document storage and document records.
- Allow PDF and image uploads.
- List uploaded documents with owner, file type, status, and timestamps.

Acceptance criteria:

- A user can log in.
- A user can upload a PDF or image.
- The original file is stored.
- A document record is created with `uploaded` status.
- The document list shows the uploaded record.

Dependency decisions:

- Use local storage first unless production S3-compatible storage is already available.
- Do not add Scout, Meilisearch, Horizon, or Spatie packages yet unless needed by implementation.

## Phase 2: AI Processing and Raw JSON Storage

Connect uploads to `ai.raraxuan.com` through a queued processing flow.

Planned work:

- Create a queued document processing job.
- Send document ID, file URL, document type, and callback URL to the AI service.
- Add the AI callback route and handler.
- Store raw AI JSON and field records.
- Mark documents as `processing`, `needs_review`, or `failed`.

Acceptance criteria:

- Uploading or manually processing a document dispatches the AI job.
- The AI service receives the expected payload.
- A successful callback stores `ai_raw_json`, confidence, and extracted field records.
- Failed requests or callbacks mark the document as `failed` with enough context to debug.

Dependency decisions:

- Use the default Laravel queue locally.
- Add Redis and Horizon when queue visibility and production retry management are needed.

## Phase 3: Filament Review Screen

Build the human review workflow.

Planned work:

- Add document detail screens for preview, AI result, extracted fields, and notes.
- Allow reviewers to edit corrected values.
- Add approve and reject actions for extracted fields.
- Add document approval once review is complete.
- Add reprocess action for failed or low-quality extraction.

Acceptance criteria:

- A reviewer can compare the uploaded document with extracted fields.
- A reviewer can correct a value.
- A reviewer can approve or reject each field.
- A document cannot become final approved data without human review.
- Reprocessing creates a new AI processing attempt without losing audit history.

Dependency decisions:

- Add Spatie Media Library only if native Filament file handling is not enough.
- Keep mark detection in raw JSON until the UI needs structured mark review.

## Phase 4: Search, Filters, and Export

Make approved data useful outside the review screen.

Planned work:

- Add filters for status, owner, dates, field keys, and low confidence.
- Add search across approved document data.
- Add CSV, Excel, or PDF exports based on business needs.
- Ensure exports use reviewed values, not raw AI values.

Acceptance criteria:

- Users can find approved documents by key fields.
- Users can filter review queues and approved records.
- Exports include only approved or explicitly selected reviewed data.
- Pending and rejected AI values are excluded from final exports.

Dependency decisions:

- Add Laravel Scout and Meilisearch only when database search is too slow or too limited.
- Use private storage for generated exports in production.

## Phase 5: Roles, Client Portal, and Audit Logs

Harden the app for multi-user and client-facing workflows.

Planned work:

- Add role and permission management.
- Separate client access from internal reviewer/admin access.
- Add audit logs for upload, AI processing, field correction, approval, rejection, reprocessing, and export.
- Add client-facing document views where needed.

Acceptance criteria:

- Clients can access only their permitted documents.
- Reviewers can review but cannot manage system settings unless permitted.
- Admins can manage users and permissions.
- Important document lifecycle actions are auditable.

Dependency decisions:

- Add Spatie Permission for roles and permissions.
- Choose an audit logging package or first-party audit table when requirements are clear.
