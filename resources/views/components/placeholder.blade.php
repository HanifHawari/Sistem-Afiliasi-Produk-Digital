@props(['owner', 'branch', 'design' => null])

{{-- Penanda halaman yang belum dikerjakan. Hapus komponen ini saat halaman mulai dibuat. --}}
<div class="rounded-2xl border border-dashed border-outline-variant bg-white p-space-lg">
    <div class="flex items-start gap-space-md">
        <span class="material-symbols-outlined rounded-xl bg-primary-fixed p-2 text-on-primary-fixed-variant">construction</span>
        <div class="space-y-space-sm">
            <p class="font-headline-sm text-headline-sm">Halaman ini belum dikerjakan</p>
            <p class="text-on-surface-variant">
                PIC: <span class="font-label-md text-label-md text-on-surface">{{ $owner }}</span>
                &middot; Branch: <code class="rounded bg-surface-container-low px-1.5 py-0.5 text-primary">{{ $branch }}</code>
            </p>
            @if ($design)
                <p class="text-on-surface-variant">Acuan desain: <code class="rounded bg-surface-container-low px-1.5 py-0.5">{{ $design }}</code></p>
            @endif
            @if ($slot->isNotEmpty())
                <div>
                    <p class="font-label-md text-label-md">Yang perlu dibuat:</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-5 text-on-surface-variant">{{ $slot }}</ul>
                </div>
            @endif
        </div>
    </div>
</div>
