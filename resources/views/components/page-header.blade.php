@props(['title', 'eyebrow' => null, 'description' => null])

<div class="mb-space-lg flex flex-col gap-space-md md:flex-row md:items-end md:justify-between">
    <div>
        @if ($eyebrow)
            <p class="font-label-sm text-label-sm uppercase text-primary">{{ $eyebrow }}</p>
        @endif
        <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface md:font-headline-lg md:text-headline-lg">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 max-w-2xl text-on-surface-variant">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap gap-space-sm">{{ $actions }}</div>
    @endisset
</div>
