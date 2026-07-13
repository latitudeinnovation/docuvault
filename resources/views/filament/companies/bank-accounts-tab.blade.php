@php
    /** @var \App\Models\Company $company */
    $company = $getRecord();
    $company->loadMissing('bankAccounts');
@endphp

<div style="display:flex;flex-direction:column;gap:1rem">
    @forelse ($company->bankAccounts as $bankAccount)
        <x-filament::section icon="heroicon-o-banknotes" icon-color="success" compact>
            <x-slot name="heading">{{ $bankAccount->account_no }}</x-slot>

            @if ($bankAccount->account_holder_name)
                <x-slot name="description">{{ $bankAccount->account_holder_name }}</x-slot>
            @endif

            <dl style="margin:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(12rem,1fr));gap:1rem 1.5rem">
                <div style="display:flex;flex-direction:column;gap:0.15rem;min-width:0">
                    <dt style="font-size:0.7rem;font-weight:500;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em">Bank / Product</dt>
                    <dd style="margin:0;font-size:0.875rem;color:#1f2937">{{ $bankAccount->bank_name ?: '—' }}</dd>
                </div>
                <div style="display:flex;flex-direction:column;gap:0.15rem;min-width:0">
                    <dt style="font-size:0.7rem;font-weight:500;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em">Account Type</dt>
                    <dd style="margin:0;font-size:0.875rem;color:#1f2937">{{ $bankAccount->account_type ?: '—' }}</dd>
                </div>
                <div style="display:flex;flex-direction:column;gap:0.15rem;min-width:0">
                    <dt style="font-size:0.7rem;font-weight:500;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em">Currency</dt>
                    <dd style="margin:0;font-size:0.875rem;color:#1f2937">{{ $bankAccount->currency ?: '—' }}</dd>
                </div>
            </dl>
        </x-filament::section>
    @empty
        <p style="font-size:0.875rem;color:#6b7280;margin:0">No bank accounts linked to this company yet.</p>
    @endforelse
</div>
