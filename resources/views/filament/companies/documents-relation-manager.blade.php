@php
    use App\Enums\DocumentType;

    /** @var \App\Models\Company $company */
    $documents = $company->documents()
        ->with('extractedFields')
        ->orderByRaw('processed_at IS NULL, processed_at DESC')
        ->get();

    $order = [DocumentType::Ssm->value, DocumentType::BankAccount->value, DocumentType::General->value];

    $groups = $documents->groupBy(fn ($d) => DocumentType::fromValue($d->document_type)->value);

    $tabKeys = collect($order)
        ->filter(fn ($k) => $groups->has($k))
        ->merge($groups->keys()->diff($order))
        ->values();
@endphp

<div>
    <style>[x-cloak]{display:none !important}</style>

    @if ($tabKeys->isEmpty())
        <p style="color:#6b7280;font-size:0.875rem;margin:0">No documents have been extracted for this company yet.</p>
    @else
        <div x-data="{ type: 0 }" style="display:flex;flex-direction:column;gap:1.5rem">
            <x-filament::tabs>
                @foreach ($tabKeys as $key)
                    @php $type = DocumentType::fromValue($key); @endphp
                    <x-filament::tabs.item
                        tag="button"
                        :icon="$type->getIcon()"
                        :badge="$groups[$key]->count()"
                        x-on:click="type = {{ $loop->index }}"
                        :alpine-active="'type === '.$loop->index"
                    >
                        {{ $type->getLabel() }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>

            @foreach ($tabKeys as $key)
                <div x-show="type === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif style="display:flex;flex-direction:column;gap:1.25rem">
                    @if ($key === DocumentType::BankAccount->value)
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
                        @foreach ($groups[$key] as $document)
                            @include('filament.companies._document-fields', ['document' => $document])
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
