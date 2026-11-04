@props(['tone' => 'neutral', 'dot' => false, 'pulse' => false])

{{--
    Badge status. tone: success | warning | danger | info | neutral
    Contoh: <x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge>
--}}
@php
    $class = [
        'success' => 'pill-green',
        'warning' => 'pill-amber',
        'danger' => 'pill-red',
        'info' => 'pill-blue',
        'neutral' => 'pill-gray',
    ][$tone] ?? 'pill-gray';
@endphp

<span {{ $attributes->class($class) }}>
    @if ($dot)
        <span @class(['h-1.5 w-1.5 rounded-full bg-current', 'animate-pulse' => $pulse])></span>
    @endif
    {{ $slot }}
</span>
