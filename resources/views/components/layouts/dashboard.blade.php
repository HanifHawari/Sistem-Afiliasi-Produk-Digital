@props(['title' => null, 'area' => ''])

{{--
    Kerangka dashboard (header + sidebar).
    Dipakai oleh <x-layouts.admin> dan <x-layouts.affiliate>, jangan dipanggil langsung.
    Slot: $nav (menu sidebar), $sidebarFooter (kartu bawah sidebar), $headerActions (tombol di header).
--}}
@php
    $roleLabel = Auth::user()->isAdmin() ? '' : Auth::user()->role->label();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased" data-layout="dashboard" x-data="{ sidebarOpen: false }">
    {{-- Header --}}
    <header class="fixed inset-x-0 top-0 z-40 h-16 border-b border-slate-200/80 bg-white lg:h-20">
        <div class="flex h-full items-center justify-between px-4 lg:px-10">
            <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                <button type="button" class="-ml-2 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = true" aria-label="Buka menu">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                {{-- Logo & nama aplikasi: di header hanya untuk layar lebar; di HP pindah ke menu samping --}}
                <a href="{{ route('dashboard') }}" class="hidden min-w-0 items-center gap-3 lg:flex">
                    @include('partials.brand', ['area' => $area])
                </a>
            </div>

            <div class="flex shrink-0 items-center gap-2 sm:gap-4">
                {{ $headerActions ?? '' }}

                <span class="hidden h-10 w-px bg-slate-200 sm:block"></span>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-3 rounded-xl p-1 hover:bg-slate-50">
                            <x-avatar :user="Auth::user()" class="h-10 w-10 text-base lg:h-11 lg:w-11 lg:text-lg" />
                            <span class="hidden flex-col text-left leading-tight sm:flex">
                                <span class="text-[15px] font-semibold text-slate-900">{{ Auth::user()->name }}</span>
                                <span class="mt-1 text-sm text-slate-400">{{ $roleLabel }}</span>
                            </span>
                            <span class="material-symbols-outlined icon-light hidden text-[22px] text-slate-400 sm:inline">expand_more</span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                Keluar
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </header>

    {{-- Overlay sidebar (mobile) --}}
    <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-[45] bg-slate-900/40 backdrop-blur-sm lg:hidden"></div>

    {{-- Sidebar --}}
    <aside
        class="fixed bottom-0 left-0 top-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200/80 bg-white px-4 py-5 transition-transform lg:top-20 lg:z-30 lg:translate-x-0 lg:py-6 2xl:w-80 2xl:px-5"
        :class="sidebarOpen && '!translate-x-0'"
    >
        {{-- Logo & nama aplikasi di menu samping (HP & tablet) --}}
        <div class="mb-6 flex items-center justify-between gap-2 border-b border-slate-100 pb-5 lg:hidden">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                @include('partials.brand', ['area' => $area])
            </a>
            <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="sidebarOpen = false" aria-label="Tutup menu">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <nav class="flex flex-1 flex-col gap-1 overflow-y-auto" data-enter-group="left">
            {{ $nav }}
        </nav>

        @isset($sidebarFooter)
            <div class="mt-4" data-enter="left">{{ $sidebarFooter }}</div>
        @endisset
    </aside>

    {{-- Konten --}}
    <main class="min-h-screen px-4 pb-12 pt-[calc(4rem+1.5rem)] sm:px-6 lg:ml-72 lg:px-10 lg:pt-[calc(5rem+2.5rem)] 2xl:ml-80">
        {{ $slot }}
    </main>

    <x-toast />
</body>
</html>
