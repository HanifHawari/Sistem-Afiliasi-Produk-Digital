@props(['href', 'icon', 'active' => false, 'count' => 0])

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'flex items-center gap-space-sm whitespace-nowrap px-3 py-2.5 rounded-xl transition-all',
        'bg-primary-container text-on-primary font-label-md shadow-sm' => $active,
        'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' => ! $active,
    ]) }}>
    <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>
    <span class="min-w-0 flex-1 truncate font-label-md text-label-md">{{ $slot }}</span>
    @if ($count > 0)
        <span @class([
            'rounded-full px-1.5 py-0.5 font-caption text-caption',
            'bg-on-primary/20 text-on-primary' => $active,
            'bg-tertiary-fixed text-on-tertiary-fixed' => ! $active,
        ])>{{ $count }}</span>
    @endif
</a>
