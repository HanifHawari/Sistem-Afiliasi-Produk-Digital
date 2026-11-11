@props(['tone' => 'neutral'])

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

<span {{ $attributes->class($class) }}>{{ $slot }}</span>
