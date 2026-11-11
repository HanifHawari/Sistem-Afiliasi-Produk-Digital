@props(['title' => null])

{{-- Layout area Customer / Toko  —  PIC: Muhammad Arif Bagus --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="bg-surface font-body-md text-body-md text-on-surface antialiased">
    <header class="sticky top-0 z-40 h-16 bg-surface-container-lowest/90 backdrop-blur-md shadow-[0_1px_8px_rgba(0,0,0,0.04)]">
        <div class="mx-auto flex h-full max-w-[1440px] items-center justify-between px-space-md lg:px-margin">
            {{-- HP: logo saja, supaya menu tidak terlipat --}}
            <a href="{{ route('shop.home') }}" class="flex shrink-0 items-center gap-space-sm" aria-label="{{ config('app.name') }}">
                <x-application-logo class="h-9 w-9" />
                <span class="hidden font-headline-sm text-headline-sm text-primary sm:inline">{{ config('app.name') }}</span>
            </a>

            <nav class="flex items-center gap-2 whitespace-nowrap sm:gap-space-sm">
                @auth
                    @if (Auth::user()->isCustomer())
                        <a href="{{ route('shop.orders.index') }}" class="rounded-lg px-3 py-2 font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-low sm:px-space-md">Pesanan Saya</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-low sm:px-space-md">Dashboard</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg border border-slate-200 px-3 py-2 font-label-md text-label-md hover:bg-slate-50 sm:px-space-md">Keluar</button>
                    </form>
                    <a href="{{ route('profile.edit') }}" title="Profil" aria-label="Profil">
                        <x-avatar :user="Auth::user()" class="h-9 w-9 text-sm" />
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-low sm:px-space-md">Masuk</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-blue-600 px-3 py-2 font-label-md text-label-md text-white hover:bg-blue-700 sm:px-space-md">Daftar</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-[1440px] px-space-md py-space-lg lg:px-margin">
        {{ $slot }}
    </main>
</body>
</html>
