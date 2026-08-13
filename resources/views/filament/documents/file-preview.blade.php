@php
    /** @var \App\Models\Document $document */
    $document = $getRecord();

    $url = route('documents.preview', $document);
    $mime = strtolower((string) $document->file_type);
    $name = $document->original_file_name ?: 'file';

    $isImage = str_starts_with($mime, 'image/');
    $isPdf = str_contains($mime, 'pdf');
@endphp

<div class="fi-document-preview" style="width:100%">
    @if ($isImage)
        <img
            src="{{ $url }}"
            alt="{{ $name }}"
            style="max-width:100%;height:auto;display:block;margin:0 auto;border-radius:0.5rem"
            class="ring-1 ring-gray-200 dark:ring-white/10"
        />
    @elseif ($isPdf)
        <iframe
            src="{{ $url }}"
            title="{{ $name }}"
            style="width:100%;height:75vh;border:0;border-radius:0.5rem"
            class="ring-1 ring-gray-200 dark:ring-white/10"
        ></iframe>
    @else
        <p style="color:#6b7280;font-size:0.875rem;margin:0">
            No inline preview for this file type.
            <a href="{{ $url }}" target="_blank" rel="noopener" class="fi-link" style="text-decoration:underline">Open {{ $name }}</a>
        </p>
    @endif
</div>
