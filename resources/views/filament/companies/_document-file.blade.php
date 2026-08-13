@php
    use App\Models\DocumentType;

    /** @var \App\Models\Document $document */
    $type = DocumentType::resolve($document->document_type);

    $url = route('documents.preview', $document);
    $mime = strtolower((string) $document->file_type);
    $name = $document->original_file_name ?: 'file';

    $isImage = str_starts_with($mime, 'image/');
    $isPdf = str_contains($mime, 'pdf');

    $collapsible ??= false;
    $collapsed ??= false;
@endphp

<x-filament::section
    :icon="$type->icon"
    :icon-color="$type->color"
    compact
    :collapsible="$collapsible"
    :collapsed="$collapsed"
>
    <x-slot name="heading">{{ $document->title }}</x-slot>

    <x-slot name="description">
        {{ $document->original_file_name ?: 'document' }}@if ($document->processed_at) &middot; {{ $document->processed_at->format('d M Y') }}@endif
    </x-slot>

    <x-slot name="afterHeader">
        <x-filament::button
            tag="a"
            :href="$url"
            target="_blank"
            rel="noopener"
            size="xs"
            color="gray"
            icon="heroicon-m-arrow-down-tray"
        >
            Open file
        </x-filament::button>
    </x-slot>

    <div style="width:100%">
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
</x-filament::section>
