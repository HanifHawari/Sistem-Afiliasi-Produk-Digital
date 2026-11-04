{{--
    Notifikasi pojok kanan bawah (Stitch: toast-notification).
    Muncul otomatis dari session flash:
      return back()->with('status', 'Judul')->with('status_detail', 'Keterangan');
      return back()->with('error', 'Pesan gagal');
--}}
@php
    $isError = session()->has('error');
    $title = session('error') ?? session('status');
    // Abaikan status bawaan Breeze yang berupa kode, mis. "profile-updated"
    $show = $title && ! preg_match('/^[a-z]+(-[a-z]+)+$/', $title);
@endphp

@if ($show)
    <div x-data="{ show: false }"
         x-init="$nextTick(() => show = true); setTimeout(() => show = false, 4000)"
         x-show="show" x-cloak
         x-transition:enter="transition duration-300" x-transition:enter-start="translate-y-24 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition duration-300" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-24 opacity-0"
         class="fixed bottom-6 right-6 z-50">
        <div class="flex items-center gap-space-sm rounded-xl bg-inverse-surface px-space-lg py-3 text-inverse-on-surface shadow-xl">
            <span @class(['material-symbols-outlined text-[22px]', 'text-secondary-fixed' => ! $isError, 'text-error-container' => $isError])>
                {{ $isError ? 'error' : 'check_circle' }}
            </span>
            <div class="flex flex-col">
                <span class="font-label-md text-label-md font-bold">{{ $title }}</span>
                @if (session('status_detail'))
                    <span class="font-caption text-caption text-inverse-on-surface/80">{{ session('status_detail') }}</span>
                @endif
            </div>
            <button type="button" class="ml-space-sm text-inverse-on-surface/70 hover:text-inverse-on-surface" @click="show = false">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    </div>
@endif
