@php
    use App\Filament\Resources\Companies\CompanyResource;

    /** @var \App\Models\Shareholder $shareholder */
    $shareholder = $getRecord();
    $shareholder->loadMissing('companies');
@endphp

<div style="display:flex;flex-direction:column;gap:1.75rem">
    <div>
        <h3 style="font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;color:#9ca3af;margin:0 0 0.75rem">Companies (Shareholder)</h3>

        @forelse ($shareholder->companies as $company)
            @php $shares = $company->pivot->shares; @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:0.6rem 0;border-bottom:1px solid #f3f4f6">
                <a href="{{ CompanyResource::getUrl('view', ['record' => $company]) }}" style="font-size:0.9rem;font-weight:600;color:#b45309;text-decoration:none">{{ $company->name }}</a>
                <div style="display:flex;align-items:center;gap:0.5rem">
                    <x-filament::badge color="primary">Shareholder</x-filament::badge>
                    @if ($shares)
                        <span style="font-size:0.75rem;color:#6b7280">{{ $shares }} shares</span>
                    @endif
                </div>
            </div>
        @empty
            <p style="font-size:0.85rem;color:#9ca3af;margin:0">Not a shareholder of any company yet.</p>
        @endforelse
    </div>
</div>
