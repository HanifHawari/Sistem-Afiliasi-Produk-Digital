@props(['cols' => 4])

{{--
    Wadah kartu statistik.
    - HP: carousel geser ke samping, satu kartu utuh selebar layar, titik penunjuk posisi di bawah.
    - Tablet ke atas: grid biasa (2 kolom, lalu 3/4 kolom di layar lebar).
--}}
@php
    $grid = [
        3 => 'sm:grid-cols-3',
        4 => 'sm:grid-cols-2 xl:grid-cols-4',
    ][(int) $cols] ?? 'sm:grid-cols-2 xl:grid-cols-4';
@endphp

<div {{ $attributes->class('mb-8') }} x-data="{ active: 0, total: 0 }" x-init="total = $refs.track.children.length">
    <div x-ref="track"
         @scroll.debounce.50ms="active = Math.round($el.scrollLeft / ($el.children[0].offsetWidth + 16))"
         class="no-scrollbar -mx-4 -mb-3 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-px-4 px-4 pb-5 [&>*]:w-full [&>*]:shrink-0 [&>*]:snap-center [&>*]:snap-always sm:mx-0 sm:mb-0 sm:grid sm:gap-6 sm:overflow-visible sm:px-0 sm:pb-0 sm:[&>*]:w-auto {{ $grid }}">
        {{ $slot }}
    </div>

    {{-- Titik penunjuk posisi (HP saja) --}}
    <div class="mt-3 flex justify-center gap-1.5 sm:hidden" x-show="total > 1" x-cloak>
        <template x-for="i in total" :key="i">
            <button type="button" class="h-1.5 rounded-full transition-all"
                    :class="active === i - 1 ? 'w-5 bg-blue-600' : 'w-1.5 bg-slate-300'"
                    :aria-label="'Kartu ' + i"
                    @click="$refs.track.children[i - 1].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' })"></button>
        </template>
    </div>
</div>
