# Filament Admin

Filament should provide the upload, review, correction, approval, reprocessing, filtering, and export interface for DocuVault.

## Planned Resources

### `DocumentResource`

Primary resource for uploaded documents.

Expected capabilities:

- Upload PDF or image files.
- Show status badges for `uploaded`, `processing`, `needs_review`, `approved`, and `failed`.
- Preview the original file when allowed by storage and file type.
- Show an AI result tab with raw JSON for debugging and audit review.
- Show an extracted data tab for field-by-field review.
- Provide an approve action that finalizes reviewed data.
- Provide a reprocess action that queues another AI processing request.
- Filter by owner, status, upload date, processed date, and confidence range when needed.

### `ExtractedFieldResource`

Resource for reviewing and correcting extracted values.

Expected capabilities:

- Show field key and field label.
- Show original AI value.
- Capture corrected value.
- Show field confidence.
- Support `pending`, `approved`, and `rejected` status.
- Provide approve and reject actions.
- Filter by document, status, low confidence, and field key when needed.

### `UserResource`

Resource for client accounts and internal users.

Expected capabilities:

- Manage users who can upload, review, approve, export, or administer documents.
- Show role and permission assignments after Spatie Permission is installed.
- Keep access control explicit so clients only see their permitted documents.

## Review Screen Behavior

The reviewer should be able to inspect the original document and extracted fields together. The UI should make low-confidence fields easy to find, allow corrected values to be saved, and keep pending fields visible until they are approved or rejected.

Approving a document should require its important extracted fields to be reviewed. The exact rule can be tightened later by document type, but the MVP should prevent a document from becoming `approved` while required review work is still pending.

## Permissions

Roles and permissions should be implemented with Spatie Permission when that package is added. Until then, keep authorization rules simple and centralized with policies or gates.

Planned permissions:

- Upload documents.
- View own documents.
- View all documents.
- Review extracted fields.
- Approve documents.
- Reprocess documents.
- Export approved data.
- Manage users and roles.

## Filament Conventions

- Use Filament resource pages and actions for document workflows.
- Use actions for approve, reject, and reprocess commands.
- Use tabs or sections to separate preview, AI result, extracted data, and notes.
- Keep raw AI JSON available to admins, but do not make it the main review experience.
