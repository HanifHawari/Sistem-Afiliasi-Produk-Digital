@props(['href', 'active' => false, 'count' => null])

{{-- Satu tab di dalam <div class="tabs">. Tab aktif otomatis digeser agar terlihat di HP. --}}
<a href="{{ $href }}"
   @if ($active) aria-current="page" x-data x-init="$el.parentElement.scrollLeft = $el.offsetLeft - 24" @endif
   {{ $attributes->class([
       'flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-semibold transition-colors',
       'bg-blue-600 text-white shadow-sm shadow-blue-600/25' => $active,
       'text-slate-500 hover:bg-slate-50 hover:text-slate-800' => ! $active,
   ]) }}>
    {{ $slot }}
    @if ($count !== null)
        <span @class([
            'rounded-full px-2 py-0.5 text-xs',
            'bg-white/20 text-white' => $active,
            'bg-slate-100 text-slate-500' => ! $active,
        ])>{{ $count }}</span>
    @endif
</a>
