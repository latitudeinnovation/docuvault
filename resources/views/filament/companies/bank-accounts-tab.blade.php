@php
    /** @var \App\Models\Company $company */
    $company = $getRecord();
    $company->loadMissing('bankAccounts');
@endphp

<div>
    @forelse ($company->bankAccounts as $bankAccount)
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:0.6rem 0;border-bottom:1px solid #f3f4f6">
            <div>
                <span style="font-size:0.9rem;font-weight:600;color:#111827">{{ $bankAccount->account_no }}</span>
                @if ($bankAccount->account_holder_name)
                    <span style="font-size:0.75rem;color:#9ca3af;margin-left:0.5rem">{{ $bankAccount->account_holder_name }}</span>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
                @if ($bankAccount->bank_name)
                    <x-filament::badge color="success">{{ $bankAccount->bank_name }}</x-filament::badge>
                @endif
                @if ($bankAccount->account_type)
                    <span style="font-size:0.75rem;color:#6b7280">{{ $bankAccount->account_type }}</span>
                @endif
            </div>
        </div>
    @empty
        <p style="font-size:0.875rem;color:#6b7280;margin:0">No bank accounts linked to this company yet.</p>
    @endforelse
</div>
