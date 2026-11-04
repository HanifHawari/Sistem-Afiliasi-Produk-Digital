@props(['title', 'eyebrow' => null, 'description' => null])

{{--
    Judul halaman (Stitch). Slot opsional:
    - $meta    : teks kecil di samping eyebrow, mis. "9 transaksi butuh tindakan"
    - $actions : tombol di kanan
--}}
<div {{ $attributes->merge(['class' => 'mb-space-lg flex flex-col justify-between gap-space-md lg:flex-row lg:items-center']) }}>
    <div class="flex max-w-2xl flex-col">
        @if ($eyebrow || isset($meta))
            <div class="mb-1 flex items-center gap-space-xs">
                @if ($eyebrow)
                    <span class="font-caption text-caption font-semibold uppercase tracking-wider text-primary">{{ $eyebrow }}</span>
                @endif
                @isset($meta)
                    <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span>
                    <span class="font-caption text-caption font-medium text-secondary">{{ $meta }}</span>
                @endisset
            </div>
        @endif
        <h1 class="font-headline-lg-mobile text-headline-lg-mobile tracking-tight text-on-background md:font-headline-lg md:text-headline-lg">{{ $title }}</h1>
        @if ($description)
            <p class="mt-0.5 font-body-md text-body-md text-on-surface-variant">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex w-full flex-wrap items-center gap-space-sm sm:w-auto lg:self-center [&>*]:flex-1 [&>*]:justify-center [&>*]:whitespace-nowrap sm:[&>*]:flex-none [&>form>button]:w-full [&>form>button]:justify-center">{{ $actions }}</div>
    @endisset
</div>
