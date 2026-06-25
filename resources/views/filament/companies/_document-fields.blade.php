@php
    use App\Enums\DocumentType;
    use App\Support\JsonPresenter;

    /** @var \App\Models\Document $document */
    $fields = $document->extractedFields;
    $json = JsonPresenter::pretty(JsonPresenter::normalized($document)) ?? '';

    $type = DocumentType::fromValue($document->document_type);
    $confidence = $document->ai_confidence !== null ? (int) round(((float) $document->ai_confidence) * 100) : null;
@endphp

<x-filament::section :icon="$type->getIcon()" :icon-color="$type->getColor()" compact>
    <x-slot name="heading">{{ $document->title }}</x-slot>

    <x-slot name="description">
        {{ $document->original_file_name ?: 'document' }}@if ($document->processed_at) &middot; {{ $document->processed_at->format('d M Y') }}@endif
    </x-slot>

    <x-slot name="afterHeader">
        <div x-data="{ copied: false }" style="display:flex;align-items:center;gap:0.5rem">
            @if ($confidence !== null)
                <x-filament::badge :color="$confidence >= 90 ? 'success' : ($confidence >= 70 ? 'warning' : 'danger')">
                    {{ $confidence }}% confidence
                </x-filament::badge>
            @endif

            <x-filament::button
                size="xs"
                color="gray"
                icon="heroicon-m-clipboard-document"
                x-on:click="navigator.clipboard.writeText(@js($json)); copied = true; setTimeout(() => copied = false, 1500)"
            >
                <span x-show="! copied">Copy JSON</span>
                <span x-show="copied" x-cloak>Copied!</span>
            </x-filament::button>
        </div>
    </x-slot>

    @if ($fields->isEmpty())
        <p style="font-size:0.875rem;color:#6b7280;margin:0">No fields were extracted for this document.</p>
    @else
        <dl style="margin:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(15rem,1fr));gap:1rem 1.5rem">
            @foreach ($fields as $field)
                @php
                    $value = trim((string) ($field->corrected_value ?? $field->value ?? ''));
                    $isMoney = (bool) preg_match('/balance|amount|total|capital|debit|credit/i', (string) $field->field_key);
                    $display = ($isMoney && $value !== '' && preg_match('/^[\d.,]+$/', $value)) ? 'RM '.$value : $value;
                @endphp
                <div style="display:flex;flex-direction:column;gap:0.15rem;min-width:0">
                    <dt style="font-size:0.7rem;font-weight:500;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em">{{ $field->field_label }}</dt>
                    <dd style="margin:0;font-size:0.875rem;overflow-wrap:anywhere;{{ $isMoney ? 'font-weight:600;font-variant-numeric:tabular-nums;color:#111827' : 'color:#1f2937' }}">{{ $display !== '' ? $display : '—' }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</x-filament::section>
