{{-- Logo petir + nama aplikasi + area (mis. PANEL ADMIN). Dipakai di header & menu samping. --}}
<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm shadow-blue-600/30 lg:h-12 lg:w-12">
    <span class="material-symbols-outlined text-[24px] lg:text-[26px]">bolt</span>
</span>
<span class="flex min-w-0 flex-col leading-tight">
    <span class="truncate text-base font-bold tracking-tight text-slate-900 lg:text-xl">{{ config('app.name') }}</span>
    <span class="text-[11px] font-semibold uppercase tracking-wide text-blue-600 lg:text-sm">{{ $area }}</span>
</span>
