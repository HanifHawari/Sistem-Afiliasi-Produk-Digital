@props(['inverse' => false])

{{--
    Logo aplikasi: ikon kotak produk. Ukuran diatur lewat class, mis. <x-application-logo class="h-10 w-10" />
    inverse: kotak putih + ikon biru, untuk dipasang di atas latar biru.
--}}
<span {{ $attributes->class([
    'inline-flex shrink-0 items-center justify-center rounded-xl',
    'bg-blue-600 text-white shadow-sm shadow-blue-600/30' => ! $inverse,
    'bg-white text-blue-600 shadow-lg shadow-blue-900/20' => $inverse,
]) }}>
    <span class="material-symbols-outlined text-[1.5em]">package_2</span>
</span>
