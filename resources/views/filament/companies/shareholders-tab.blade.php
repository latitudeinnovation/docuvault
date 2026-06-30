@php
    use App\Filament\Resources\Shareholders\ShareholderResource;

    /** @var \App\Models\Company $company */
    $company = $getRecord();
    $company->loadMissing('shareholders');
@endphp

<div>
    @forelse ($company->shareholders as $shareholder)
        @php $shares = $shareholder->pivot->shares; @endphp
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:0.6rem 0;border-bottom:1px solid #f3f4f6">
            <div>
                <a href="{{ ShareholderResource::getUrl('view', ['record' => $shareholder]) }}" style="font-size:0.9rem;font-weight:600;color:#b45309;text-decoration:none">{{ $shareholder->name }}</a>
                @if ($shareholder->ic_passport)
                    <span style="font-size:0.75rem;color:#9ca3af;margin-left:0.5rem">{{ $shareholder->ic_passport }}</span>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem">
                <x-filament::badge color="warning">Shareholder</x-filament::badge>
                @if ($shares)
                    <span style="font-size:0.75rem;color:#6b7280">{{ number_format((int) str_replace(',', '', $shares)) }} shares</span>
                @endif
            </div>
        </div>
    @empty
        <p style="font-size:0.875rem;color:#6b7280;margin:0">No shareholders linked to this company yet.</p>
    @endforelse
</div>
