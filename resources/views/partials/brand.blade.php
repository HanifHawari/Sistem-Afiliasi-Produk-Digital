{{-- Logo + nama aplikasi + area (mis. PANEL ADMIN). Dipakai di header & menu samping. --}}
<x-application-logo class="h-10 w-10 text-base lg:h-12 lg:w-12 lg:text-lg" />
<span class="flex min-w-0 flex-col leading-tight">
    <span class="truncate text-base font-bold tracking-tight text-slate-900 lg:text-xl">{{ config('app.name') }}</span>
    <span class="text-[11px] font-semibold uppercase tracking-wide text-blue-600 lg:text-sm">{{ $area }}</span>
</span>
