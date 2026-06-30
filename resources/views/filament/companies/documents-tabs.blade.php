@php
    use App\Enums\DocumentType as DocumentTypeEnum;
    use App\Models\DocumentType;

    /** @var \App\Models\Company $company */
    $company = $getRecord();

    $documents = $company->documents()
        ->with('extractedFields')
        ->orderByRaw('processed_at IS NULL, processed_at DESC')
        ->get();

    // Tab order follows the admin-managed document types, then any other types
    // still present on documents (e.g. a since-deleted type).
    $types = DocumentType::ordered();
    $order = $types->keys()->all();

    $groups = $documents->groupBy(fn ($d) => (string) ($d->document_type ?: DocumentTypeEnum::General->value));

    $tabKeys = collect($order)
        ->filter(fn ($k) => $groups->has($k))
        ->merge($groups->keys()->diff($order))
        ->values();
@endphp

<style>[x-cloak]{display:none !important}</style>

@if ($tabKeys->isEmpty())
    <p style="color:#6b7280;font-size:0.875rem;margin:0">No documents have been extracted for this company yet.</p>
@else
    <div x-data="{ type: 0 }" style="display:flex;flex-direction:column;gap:1.5rem">
        {{-- Document-type selector --}}
        <x-filament::tabs>
            @foreach ($tabKeys as $key)
                @php $type = $types[$key] ?? DocumentType::resolve($key); @endphp
                <x-filament::tabs.item
                    tag="button"
                    :icon="$type->icon"
                    :badge="$groups[$key]->count()"
                    x-on:click="type = {{ $loop->index }}"
                    :alpine-active="'type === '.$loop->index"
                >
                    {{ $type->label }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>

        {{-- Type panels --}}
        @foreach ($tabKeys as $key)
            <div x-show="type === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif style="display:flex;flex-direction:column;gap:1.25rem">
                @if ($key === DocumentTypeEnum::BankAccount->value)
                    {{-- Bank accounts grouped by statement month --}}
                    @php
                        $byMonth = $groups[$key]
                            ->sortBy(fn ($d) => optional($d->periodDate())->getTimestamp() ?? 0)
                            ->groupBy(fn ($d) => $d->periodLabel());
                        $monthKeys = $byMonth->keys()->values();
                    @endphp

                    <div x-data="{ month: 0 }" style="display:flex;flex-direction:column;gap:1.25rem">
                        <x-filament::tabs contained>
                            @foreach ($monthKeys as $month)
                                <x-filament::tabs.item
                                    tag="button"
                                    :badge="$byMonth[$month]->count()"
                                    x-on:click="month = {{ $loop->index }}"
                                    :alpine-active="'month === '.$loop->index"
                                >
                                    {{ $month }}
                                </x-filament::tabs.item>
                            @endforeach
                        </x-filament::tabs>

                        @foreach ($monthKeys as $month)
                            <div x-show="month === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif style="display:flex;flex-direction:column;gap:1.25rem">
                                @foreach ($byMonth[$month] as $document)
                                    @include('filament.companies._document-fields', ['document' => $document])
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- SSM / other types: newest record expanded, older records collapsed --}}
                    <div style="display:flex;flex-direction:column;gap:2rem">
                        @foreach ($groups[$key] as $document)
                            @include('filament.companies._document-fields', [
                                'document' => $document,
                                'collapsible' => ! $loop->first,
                                'collapsed' => ! $loop->first,
                            ])
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
