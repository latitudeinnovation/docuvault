# Data Model

This is the planned data model for document processing. It should be implemented with migrations only when the feature work begins.

## `documents`

Stores one uploaded document and its overall processing state.

| Field | Purpose |
| --- | --- |
| `id` | Primary key. |
| `user_id` | User who uploaded or owns the document. |
| `title` | Human-readable title. |
| `file_path` | Stored original PDF or image path. |
| `file_type` | Document MIME/type classification. |
| `status` | Processing lifecycle state. |
| `ai_confidence` | Overall AI confidence score, if returned. |
| `ai_raw_json` | Full raw callback payload for audit and debugging. |
| `processed_at` | Timestamp when AI processing completed. |

Recommended document statuses:

- `uploaded`
- `processing`
- `needs_review`
- `approved`
- `failed`

## `document_pages`

Stores per-page derived data when a document is split into images or OCR text.

| Field | Purpose |
| --- | --- |
| `id` | Primary key. |
| `document_id` | Parent document. |
| `page_no` | One-based page number. |
| `image_path` | Stored page image path, if generated. |
| `ocr_text` | OCR text for the page, if available. |

## `extracted_fields`

Stores individual values returned by AI and the human-reviewed correction state.

| Field | Purpose |
| --- | --- |
| `id` | Primary key. |
| `document_id` | Parent document. |
| `field_key` | Stable machine key such as `customer_name`. |
| `field_label` | Human-readable field label. |
| `value` | Original AI-extracted value. |
| `confidence` | Field-level confidence score. |
| `corrected_value` | Human-corrected value, when changed. |
| `status` | Review state for this field. |

Recommended extracted field statuses:

- `pending`
- `approved`
- `rejected`

For search and export, use the reviewed value. Prefer `corrected_value` when present and approved; otherwise use the approved AI value. Rejected and pending values should not be treated as final business data.

## `document_notes`

Stores reviewer or account notes attached to a document.

| Field | Purpose |
| --- | --- |
| `id` | Primary key. |
| `document_id` | Parent document. |
| `user_id` | User who wrote the note. |
| `note` | Note content. |

## Implementation Notes

- Keep raw AI data separate from reviewed business data.
- Add indexes for common filters when implementing migrations, especially `documents.user_id`, `documents.status`, `extracted_fields.document_id`, and `extracted_fields.status`.
- Use model relationships for document pages, extracted fields, notes, and owner user records.
- Avoid making the database schema depend on one document type too early; start with flexible extracted fields and add typed tables later only when needed.
