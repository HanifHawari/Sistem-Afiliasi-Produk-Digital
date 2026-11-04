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
        {{-- HP: tombol berdampingan, lebih ringkas & tanpa ikon. Tablet ke atas: ukuran normal. --}}
        <div class="flex w-full items-center gap-2 sm:w-auto sm:gap-3 lg:shrink-0
                    [&>*]:min-w-0 [&>*]:flex-1 sm:[&>*]:flex-none
                    [&>a]:px-3 [&>a]:py-2.5 [&>a]:text-sm [&>button]:px-3 [&>button]:py-2.5 [&>button]:text-sm [&>form>button]:w-full [&>form>button]:px-3 [&>form>button]:py-2.5 [&>form>button]:text-sm
                    sm:[&>a]:px-5 sm:[&>a]:py-3 sm:[&>a]:text-[15px] sm:[&>button]:px-5 sm:[&>button]:py-3 sm:[&>button]:text-[15px] sm:[&>form>button]:px-5 sm:[&>form>button]:py-3 sm:[&>form>button]:text-[15px]
                    [&_.material-symbols-outlined]:hidden sm:[&_.material-symbols-outlined]:inline">{{ $actions }}</div>
    @endisset
</div>
