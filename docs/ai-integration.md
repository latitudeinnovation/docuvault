# AI Integration

DocuVault sends uploaded documents to `ai.raraxuan.com` for OCR and structured extraction. AI output starts as untrusted data and must pass through human review before it becomes final.

## Processing Endpoint

Laravel should send processing requests to:

```text
POST https://ai.raraxuan.com/api/process-document
```

Example payload:

```json
{
  "document_id": 123,
  "file_url": "https://your-app.com/storage/documents/file.pdf",
  "document_type": "invoice",
  "callback_url": "https://your-app.com/api/ai/callback"
}
```

Use a queued job for this request. The job should mark the document as `processing`, generate a temporary or otherwise private file URL where possible, call the AI service with explicit timeout and retry behavior, and mark the document as `failed` if the request cannot be accepted.

## Callback Route

Laravel should expose this callback contract:

```text
POST /api/ai/callback
Route name: ai.callback
```

Example successful callback payload:

```json
{
  "document_id": 123,
  "status": "success",
  "confidence": 0.91,
  "fields": [
    {
      "key": "customer_name",
      "label": "Customer Name",
      "value": "ABC Sdn Bhd",
      "confidence": 0.95
    },
    {
      "key": "invoice_total",
      "label": "Invoice Total",
      "value": "1250.00",
      "confidence": 0.88
    }
  ],
  "marks": [
    {
      "type": "tick",
      "page": 1,
      "label": "approved",
      "confidence": 0.82
    },
    {
      "type": "circle",
      "page": 2,
      "text_inside": "Item 3",
      "confidence": 0.77
    }
  ]
}
```

## Callback Handling

The callback handler should:

- Validate the payload with a Form Request or equivalent request validation.
- Find the document by `document_id`.
- Reject or ignore callbacks for documents that cannot accept new AI results.
- Wrap document and field persistence in a database transaction.
- Store the full raw payload in `documents.ai_raw_json` for audit and debugging.
- Update document status to `needs_review` when extraction succeeds.
- Create `extracted_fields` rows with `pending` status.
- Preserve mark detection data either in raw JSON first or in a dedicated table later if the UI needs structured mark review.
- Return JSON confirming whether the callback was accepted.

## Safety Rules

- Do not blindly trust or approve AI values.
- Do not treat unknown callback fields as approved business data.
- Do not expose private documents with permanent public URLs unless the product explicitly requires it.
- Use temporary URLs for S3-compatible storage when available.
- Validate MIME type, extension, and upload size before any AI processing.
- Use HTTP timeout, connection timeout, retry, and failure handling for the external API call.
- Log enough context to debug failed processing without logging sensitive document contents.
