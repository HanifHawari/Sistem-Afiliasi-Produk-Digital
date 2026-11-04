@props(['tone' => 'neutral', 'dot' => true])

{{--
    Badge status (DESIGN.md: Badges). tone: success | warning | danger | info | neutral
    Contoh: <x-badge tone="warning">Menunggu Verifikasi</x-badge>
--}}
@php
    $tones = [
        'success' => ['bg-[#ECFDF5] text-[#059669]', 'bg-[#059669]'],
        'warning' => ['bg-[#FFFBEB] text-[#D97706]', 'bg-[#D97706]'],
        'danger' => ['bg-[#FEF2F2] text-[#DC2626]', 'bg-[#DC2626]'],
        'info' => ['bg-primary-fixed text-on-primary-fixed-variant', 'bg-primary-container'],
        'neutral' => ['bg-slate-100 text-slate-600', 'bg-slate-400'],
    ];
    [$color, $dotColor] = $tones[$tone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-label-sm text-label-sm $color"]) }}>
    @if ($dot)
        <span class="h-1.5 w-1.5 rounded-full {{ $dotColor }}"></span>
    @endif
    {{ $slot }}
</span>
