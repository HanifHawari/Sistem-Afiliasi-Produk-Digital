@props(['tone' => 'neutral', 'dot' => true, 'pulse' => false])

{{--
    Badge status (warna mengikuti code.html Stitch).
    tone: success | warning | danger | info | neutral
    Contoh: <x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge>
--}}
@php
    $tones = [
        'success' => ['bg-secondary-fixed text-on-secondary-fixed', 'bg-secondary'],
        'warning' => ['bg-tertiary-fixed text-on-tertiary-fixed', 'bg-tertiary'],
        'danger' => ['bg-error-container text-on-error-container', 'bg-error'],
        'info' => ['bg-primary-fixed text-on-primary-fixed-variant', 'bg-primary'],
        'neutral' => ['bg-surface-container-highest text-on-surface-variant', 'bg-outline'],
    ];
    [$color, $dotColor] = $tones[$tone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-1 font-label-sm text-label-sm $color"]) }}>
    @if ($dot)
        <span @class(['h-1.5 w-1.5 rounded-full', $dotColor, 'animate-pulse' => $pulse])></span>
    @endif
    {{ $slot }}
</span>
