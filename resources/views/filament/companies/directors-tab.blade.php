@php
    use App\Filament\Resources\Directors\DirectorResource;

    /** @var \App\Models\Company $company */
    $company = $getRecord();
    $company->loadMissing('directors');
@endphp

<div>
    @forelse ($company->directors as $director)
        @php
            $designation = $director->pivot->designation;
            $appointed   = $director->pivot->appointed_at;
        @endphp
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:0.6rem 0;border-bottom:1px solid #f3f4f6">
            <div>
                <a href="{{ DirectorResource::getUrl('view', ['record' => $director]) }}" style="font-size:0.9rem;font-weight:600;color:#b45309;text-decoration:none">{{ $director->name }}</a>
                @if ($director->ic_passport)
                    <span style="font-size:0.75rem;color:#9ca3af;margin-left:0.5rem">{{ $director->ic_passport }}</span>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
                @if ($designation)
                    <x-filament::badge color="primary">{{ $designation }}</x-filament::badge>
                @endif
                @if ($appointed)
                    <span style="font-size:0.75rem;color:#6b7280">Appointed {{ \Illuminate\Support\Carbon::parse($appointed)->format('d M Y') }}</span>
                @endif
            </div>
        </div>
    @empty
        <p style="font-size:0.875rem;color:#6b7280;margin:0">No directors linked to this company yet.</p>
    @endforelse
</div>
