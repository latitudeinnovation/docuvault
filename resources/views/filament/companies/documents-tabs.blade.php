@php
    use App\Enums\DocumentType;
    use App\Support\JsonPresenter;

    /** @var \App\Models\Company $company */
    $company = $getRecord();

    $documents = $company->documents()
        ->orderByRaw('processed_at IS NULL, processed_at DESC')
        ->get();

    // Tab order: SSM, Bank Account, General, then any other types present.
    $order = [DocumentType::Ssm->value, DocumentType::BankAccount->value, DocumentType::General->value];

    $groups = $documents->groupBy(fn ($d) => DocumentType::fromValue($d->document_type)->value);

    $tabKeys = collect($order)
        ->filter(fn ($k) => $groups->has($k))
        ->merge($groups->keys()->diff($order))
        ->values();
@endphp

@if ($tabKeys->isEmpty())
    <p style="color:#6b7280;font-size:0.875rem">No documents have been extracted for this company yet.</p>
@else
    <div x-data="{ active: @js($tabKeys->first()) }">
        {{-- Tab selector --}}
        <div style="display:flex;gap:1.25rem;border-bottom:1px solid #e5e7eb;margin-bottom:1.25rem">
            @foreach ($tabKeys as $key)
                @php $type = DocumentType::fromValue($key); @endphp
                <button
                    type="button"
                    x-on:click="active = @js($key)"
                    x-bind:style="active === @js($key)
                        ? 'border-bottom:2px solid #d97706;color:#b45309;font-weight:600'
                        : 'border-bottom:2px solid transparent;color:#6b7280;font-weight:500'"
                    style="margin-bottom:-1px;padding:0.5rem 0.25rem;font-size:0.875rem;background:none;cursor:pointer;display:inline-flex;align-items:center;gap:0.4rem"
                >
                    {{ $type->getLabel() }}
                    <span style="background:#f3f4f6;border-radius:9999px;padding:0.05rem 0.5rem;font-size:0.7rem;color:#4b5563">{{ $groups[$key]->count() }}</span>
                </button>
            @endforeach
        </div>

        {{-- Tab panels --}}
        @foreach ($tabKeys as $key)
            <div x-show="active === @js($key)" @if (! $loop->first) x-cloak @endif style="display:flex;flex-direction:column;gap:1.25rem">
                @foreach ($groups[$key] as $document)
                    @php
                        $payload = JsonPresenter::normalized($document);
                        $html = JsonPresenter::html($payload);
                    @endphp
                    <div
                        x-data="{ copied: false, copy() { navigator.clipboard.writeText(@js(JsonPresenter::pretty($payload) ?? '')); this.copied = true; setTimeout(() => this.copied = false, 1500); } }"
                        style="border:1px solid #e5e7eb;border-radius:0.5rem;overflow:hidden"
                    >
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:0.6rem 0.9rem;background:#fafafa;border-bottom:1px solid #eee">
                            <div style="min-width:0">
                                <div style="font-size:0.875rem;font-weight:600;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $document->title }}</div>
                                <div style="font-size:0.72rem;color:#9ca3af">
                                    {{ $document->original_file_name ?: 'document' }}
                                    @if ($document->processed_at)
                                        · {{ $document->processed_at->format('d M Y') }}
                                    @endif
                                </div>
                            </div>
                            <button
                                type="button"
                                x-on:click="copy()"
                                style="flex-shrink:0;display:inline-flex;align-items:center;gap:0.25rem;border-radius:0.375rem;background:#f3f4f6;padding:0.3rem 0.7rem;font-size:0.72rem;font-weight:500;color:#4b5563;cursor:pointer;border:1px solid rgba(0,0,0,0.06)"
                            >
                                <span x-show="! copied">Copy</span>
                                <span x-show="copied" x-cloak>Copied!</span>
                            </button>
                        </div>
                        <pre style="margin:0;white-space:pre-wrap;overflow-wrap:anywhere;overflow-x:auto;background:#f9fafb;padding:1rem;font-family:ui-monospace,monospace;font-size:0.75rem;line-height:1.6;color:#374151">@if ($html){!! $html !!}@else{{ '—' }}@endif</pre>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endif
