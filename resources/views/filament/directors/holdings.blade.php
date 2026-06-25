@php
    use App\Filament\Resources\Companies\CompanyResource;

    /** @var \App\Models\Director $director */
    $director = $getRecord();
    $director->loadMissing('companies');
    $statements = $director->bankStatements();
@endphp

<div style="display:flex;flex-direction:column;gap:1.75rem">
    {{-- Companies this person directs --}}
    <div>
        <h3 style="font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;color:#9ca3af;margin:0 0 0.75rem">Companies (Director)</h3>

        @forelse ($director->companies as $company)
            @php $appointed = $company->pivot->appointed_at; @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:0.6rem 0;border-bottom:1px solid #f3f4f6">
                <a href="{{ CompanyResource::getUrl('view', ['record' => $company]) }}" style="font-size:0.9rem;font-weight:600;color:#b45309;text-decoration:none">{{ $company->name }}</a>
                <div style="display:flex;align-items:center;gap:0.5rem">
                    <x-filament::badge color="primary">Director</x-filament::badge>
                    @if ($appointed)
                        <span style="font-size:0.75rem;color:#6b7280">Appointed {{ \Illuminate\Support\Carbon::parse($appointed)->format('d M Y') }}</span>
                    @endif
                </div>
            </div>
        @empty
            <p style="font-size:0.85rem;color:#9ca3af;margin:0">Not a director of any company yet.</p>
        @endforelse
    </div>

    {{-- Bank statements under this person's name --}}
    <div>
        <h3 style="font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;color:#9ca3af;margin:0 0 0.75rem">Bank statements under {{ $director->name }}</h3>

        @if ($statements->isEmpty())
            <p style="font-size:0.85rem;color:#9ca3af;margin:0">No bank statements are held under this name.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:1rem">
                @foreach ($statements as $document)
                    @include('filament.companies._document-fields', ['document' => $document])
                @endforeach
            </div>
        @endif
    </div>
</div>
