@props(['items'])

{{--
    Filter status.
    - Tablet & desktop: deretan tab.
    - HP: satu tombol berisi pilihan aktif; diketuk -> muncul menu semua pilihan.
    $items: list of ['label' => ..., 'href' => ..., 'active' => bool, 'count' => int|null]
--}}
@php
    $current = collect($items)->firstWhere('active', true) ?? $items[0];
@endphp

{{-- Tablet & desktop --}}
<div class="tabs hidden sm:flex">
    @foreach ($items as $item)
        <x-tab-link :href="$item['href']" :active="$item['active']" :count="$item['count'] ?? null">{{ $item['label'] }}</x-tab-link>
    @endforeach
</div>

{{-- HP --}}
<div class="relative shrink-0 sm:hidden" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" :aria-expanded="open"
            class="flex h-[42px] items-center gap-2 rounded-xl bg-blue-600 pl-4 pr-3 text-sm font-semibold text-white shadow-sm shadow-blue-600/25">
        <span class="max-w-[10rem] truncate">{{ $current['label'] }}</span>
        <span class="material-symbols-outlined text-[20px] transition-transform" :class="open && 'rotate-180'">expand_more</span>
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.left
         class="absolute left-0 top-full z-30 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg shadow-slate-900/10">
        @foreach ($items as $item)
            <a href="{{ $item['href'] }}" @class([
                'flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold',
                'bg-blue-50 text-blue-600' => $item['active'],
                'text-slate-600 hover:bg-slate-50' => ! $item['active'],
            ])>
                {{ $item['label'] }}
                @if (($item['count'] ?? null) !== null)
                    <span @class(['rounded-full px-2 py-0.5 text-xs', 'bg-blue-100 text-blue-600' => $item['active'], 'bg-slate-100 text-slate-500' => ! $item['active']])>{{ $item['count'] }}</span>
                @endif
            </a>
        @endforeach
    </div>
</div>
