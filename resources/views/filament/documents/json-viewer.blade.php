{{-- @var array<int, array{label: string, plain: ?string, html: \Illuminate\Support\HtmlString|null}> $blocks --}}
<div x-data="{ active: 0, copied: false, plains: @js(array_map(fn ($b) => $b['plain'], $blocks)),
        copy() { navigator.clipboard.writeText(this.plains[this.active] ?? ''); this.copied = true; setTimeout(() => this.copied = false, 1500); } }">

    <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e5e7eb;margin-bottom:1rem">
        <div style="display:flex;gap:1.25rem">
            @foreach ($blocks as $i => $block)
                <button
                    type="button"
                    x-on:click="active = {{ $i }}"
                    x-bind:style="active === {{ $i }}
                        ? 'border-bottom:2px solid #d97706;color:#b45309;font-weight:600'
                        : 'border-bottom:2px solid transparent;color:#6b7280;font-weight:500'"
                    style="margin-bottom:-1px;padding:0.5rem 0.75rem;font-size:0.875rem;background:none;cursor:pointer"
                >{{ $block['label'] }}</button>
            @endforeach
        </div>

        <button
            type="button"
            x-on:click="copy()"
            style="display:inline-flex;align-items:center;gap:0.25rem;border-radius:0.375rem;background:#f3f4f6;padding:0.3rem 0.7rem;font-size:0.75rem;font-weight:500;color:#4b5563;cursor:pointer;border:1px solid rgba(0,0,0,0.06)"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:0.85rem;height:0.85rem">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
            </svg>
            <span x-show="! copied">Copy</span>
            <span x-show="copied" x-cloak>Copied!</span>
        </button>
    </div>

    @foreach ($blocks as $i => $block)
        <div x-show="active === {{ $i }}" @if ($i !== 0) x-cloak @endif>
            <pre style="white-space:pre-wrap;overflow-wrap:anywhere;overflow-x:auto;background:#f9fafb;padding:1rem;border-radius:0.5rem;font-family:ui-monospace,monospace;font-size:0.75rem;line-height:1.6;color:#374151;border:1px solid rgba(0,0,0,0.05)">@if ($block['html']){!! $block['html'] !!}@else{{ '—' }}@endif</pre>
        </div>
    @endforeach
</div>
