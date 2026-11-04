@props(['title', 'description' => null, 'eyebrow' => null])

{{--
    Judul halaman: judul besar + deskripsi abu, tombol di kanan (slot $actions).
    Pakai class .btn-primary / .btn-outline untuk tombolnya. ($eyebrow tidak ditampilkan lagi.)
--}}
<div {{ $attributes->merge(['class' => 'mb-8 flex flex-col justify-between gap-5 lg:mb-10 lg:flex-row lg:items-center']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-[2.5rem] lg:leading-tight">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1.5 text-base text-slate-500 lg:text-lg">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex w-full flex-wrap items-center gap-3 sm:w-auto lg:shrink-0 [&>*]:flex-1 sm:[&>*]:flex-none [&>form>button]:w-full">{{ $actions }}</div>
    @endisset
</div>
