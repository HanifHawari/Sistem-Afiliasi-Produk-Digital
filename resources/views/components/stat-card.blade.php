@props([
    'label',
    'value',
    'icon' => null,
    'tone' => 'primary',   // primary | secondary | tertiary | error
    'caption' => null,
    'highlight' => false,  // kartu latar biru muda, mis. antrean yang perlu tindakan
    'valueClass' => 'text-on-surface',
])

{{--
    Kartu statistik (Stitch: Metric Overview Cards).
    Slot default = baris paling bawah (tren / tautan), opsional.
--}}
@php
    $iconTones = [
        'primary' => 'bg-primary-fixed text-primary',
        'secondary' => 'bg-secondary-fixed text-secondary',
        'tertiary' => 'bg-tertiary-fixed text-tertiary',
        'error' => 'bg-error-container text-error',
    ];
@endphp

<div {{ $attributes->class([
    'flex min-w-0 flex-col justify-between rounded-xl p-3 shadow-sm sm:p-space-md',
    'bg-surface-container-high' => $highlight,
    'bg-surface-container-lowest' => ! $highlight,
]) }}>
    <div class="flex items-start justify-between gap-2">
        <span @class(['font-body-sm text-body-sm', 'text-on-surface' => $highlight, 'text-on-surface-variant' => ! $highlight])>{{ $label }}</span>
        @if ($icon)
            <div @class([
                'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
                'bg-surface-container-lowest text-primary' => $highlight,
                $iconTones[$tone] ?? $iconTones['primary'] => ! $highlight,
            ])>
                <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
            </div>
        @endif
    </div>

    <div class="my-2 sm:my-3">
        <div class="tabular break-words font-currency-metric text-lg font-bold leading-6 sm:text-currency-metric {{ $valueClass }}">{{ $value }}</div>
        @if ($caption)
            <span class="block font-caption text-caption text-on-surface-variant">{{ $caption }}</span>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center justify-between gap-1.5 font-label-sm text-label-sm">{{ $slot }}</div>
    @endif
</div>
