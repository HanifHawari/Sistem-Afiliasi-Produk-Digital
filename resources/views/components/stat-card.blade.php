@props(['label', 'value', 'icon' => null])

{{-- Kartu statistik (DESIGN.md: Stat Cards). Slot default = teks kecil di bawah angka. --}}
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-space-md shadow-[0_1px_3px_rgba(30,27,75,0.04),0_1px_2px_rgba(30,27,75,0.02)]']) }}>
    <div class="flex items-start justify-between">
        <span class="font-body-sm text-body-sm text-slate-500">{{ $label }}</span>
        @if ($icon)
            <span class="material-symbols-outlined text-[20px] text-primary-container">{{ $icon }}</span>
        @endif
    </div>
    <p class="tabular mt-space-sm font-currency-metric text-currency-metric text-slate-900">{{ $value }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-space-xs font-body-sm text-body-sm text-slate-500">{{ $slot }}</div>
    @endif
</div>
