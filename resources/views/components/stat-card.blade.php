@props(['label', 'value', 'caption' => null])

{{--
    Kartu statistik biru: LABEL kapital, angka besar, keterangan, lalu garis & baris bawah (slot).
    Untuk pill di baris bawah pakai class .pill-on-blue, mis.
    <span class="pill-on-blue text-emerald-600">+12% vs lalu</span>
--}}
<div {{ $attributes->class('flex min-w-0 flex-col rounded-2xl bg-blue-600 p-4 text-white shadow-lg shadow-blue-600/20 sm:p-6') }}>
    <p class="text-[11px] font-bold uppercase tracking-wide text-white/90 sm:text-xs">{{ $label }}</p>
    <p class="tabular mt-2 break-words text-xl font-bold tracking-tight sm:mt-3 sm:text-[1.75rem] sm:leading-9">{{ $value }}</p>
    @if ($caption)
        <p class="mt-1 text-xs text-white/80 sm:text-sm">{{ $caption }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-auto pt-4">
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-white/20 pt-4 text-xs font-medium text-white/90 sm:text-sm">{{ $slot }}</div>
        </div>
    @endif
</div>
