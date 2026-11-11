@props(['paginator', 'noun' => 'data'])

{{-- Kaki tabel: "1-5 dari 34 transaksi" + tombol Sebelumnya / Berikutnya --}}
<div {{ $attributes->class('flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 sm:px-7') }}>
    <span class="text-sm text-slate-500">{{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} {{ $noun }}</span>
    <div class="flex shrink-0 items-center gap-2">
        @foreach ([['Sebelumnya', $paginator->previousPageUrl()], ['Berikutnya', $paginator->nextPageUrl()]] as [$label, $url])
            @if ($url)
                <a href="{{ $url }}" class="btn-outline btn-sm">{{ $label }}</a>
            @else
                <span class="btn-outline btn-sm cursor-not-allowed opacity-40" aria-disabled="true">{{ $label }}</span>
            @endif
        @endforeach
    </div>
</div>
