@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'flex items-center gap-space-sm px-space-md py-2.5 rounded-xl transition-all',
        'bg-primary-container text-on-primary font-label-md shadow-sm' => $active,
        'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' => ! $active,
    ]) }}>
    <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>
    <span class="font-label-md text-label-md">{{ $slot }}</span>
</a>
