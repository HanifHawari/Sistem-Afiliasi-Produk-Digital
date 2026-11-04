@props(['title' => null, 'area' => ''])

{{--
    Kerangka dashboard (header + sidebar) mengikuti desain Stitch.
    Dipakai oleh <x-layouts.admin> dan <x-layouts.affiliate>, jangan dipanggil langsung.
    Slot: $nav (menu sidebar), $sidebarFooter (kartu bawah sidebar), $headerActions (ikon di header).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="bg-surface font-body-md text-body-md text-on-surface antialiased" x-data="{ sidebarOpen: false }">
    {{-- Header --}}
    <header class="fixed inset-x-0 top-0 z-40 h-16 bg-surface-container-lowest/90 shadow-[0_1px_8px_rgba(0,0,0,0.04)] backdrop-blur-md">
        <div class="flex h-full items-center justify-between px-space-md lg:px-space-lg">
            <div class="flex items-center gap-space-sm">
                <button type="button" class="rounded-lg p-2 text-on-surface-variant hover:bg-surface-container-low lg:hidden" @click="sidebarOpen = !sidebarOpen">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-space-sm">
                    <x-application-logo class="h-9 w-9" />
                    <span class="flex flex-col leading-tight">
                        <span class="font-headline-sm text-headline-sm text-primary">{{ config('app.name') }}</span>
                        <span class="font-caption text-caption text-on-surface-variant">{{ $area }}</span>
                    </span>
                </a>
            </div>

            <div class="flex items-center gap-space-sm">
                {{ $headerActions ?? '' }}

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-space-sm rounded-xl px-space-sm py-1.5 hover:bg-surface-container-low">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-fixed font-label-md text-label-md text-on-primary-fixed-variant">
                                {{ str(Auth::user()->name)->substr(0, 1)->upper() }}
                            </span>
                            <span class="hidden flex-col text-left sm:flex">
                                <span class="font-label-md text-label-md text-on-surface">{{ Auth::user()->name }}</span>
                                <span class="font-caption text-caption text-primary">{{ Auth::user()->role->label() }}</span>
                            </span>
                            <span class="material-symbols-outlined text-[20px] text-on-surface-variant">expand_more</span>
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
    <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-on-background/40 backdrop-blur-sm lg:hidden"></div>

    {{-- Sidebar --}}
    <aside
        class="fixed bottom-0 left-0 top-16 z-30 flex w-64 -translate-x-full flex-col bg-surface-container-lowest p-space-md shadow-[0_1px_8px_rgba(0,0,0,0.04)] transition-transform lg:translate-x-0"
        :class="sidebarOpen && '!translate-x-0'"
    >
        <nav class="flex flex-1 flex-col gap-space-xs overflow-y-auto">
            {{ $nav }}
        </nav>

        @isset($sidebarFooter)
            <div class="mt-space-md">{{ $sidebarFooter }}</div>
        @endisset
    </aside>

    {{-- Konten --}}
    <main class="min-h-screen px-space-md pb-space-2xl pt-[calc(4rem+1.5rem)] lg:ml-64 lg:px-space-lg">
        {{ $slot }}
    </main>

    <x-toast />
</body>
</html>
