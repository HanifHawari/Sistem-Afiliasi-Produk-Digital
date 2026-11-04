@props(['href', 'icon', 'active' => false, 'count' => 0])

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'flex items-center gap-3.5 whitespace-nowrap rounded-xl px-4 py-3 text-[15px] transition-all 2xl:text-base',
        'bg-blue-600 font-semibold text-white shadow-md shadow-blue-600/25' => $active,
        'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! $active,
    ]) }}>
    <span @class(['material-symbols-outlined icon-light text-[22px]', 'text-white' => $active, 'text-slate-400' => ! $active])>{{ $icon }}</span>
    <span class="min-w-0 flex-1 truncate">{{ $slot }}</span>
    @if ($count > 0)
        <span @class([
            'flex h-6 min-w-[1.5rem] items-center justify-center rounded-full px-1.5 text-xs font-semibold',
            'bg-white/20 text-white' => $active,
            'bg-blue-100 text-blue-600' => ! $active,
        ])>{{ $count }}</span>
    @endif
</a>
