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
            <a href="{{ route('shop.home') }}" class="flex items-center gap-space-sm">
                <x-application-logo class="h-9 w-9" />
                <span class="font-headline-sm text-headline-sm text-primary">{{ config('app.name') }}</span>
            </a>

            <nav class="flex items-center gap-space-sm">
                @auth
                    @if (Auth::user()->isCustomer())
                        <a href="{{ route('shop.orders.index') }}" class="rounded-lg px-space-md py-2 font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-low">Pesanan Saya</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="rounded-lg px-space-md py-2 font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-low">Dashboard</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg border border-slate-200 px-space-md py-2 font-label-md text-label-md hover:bg-slate-50">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-space-md py-2 font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-low">Masuk</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-primary-container px-space-md py-2 font-label-md text-label-md text-on-primary hover:bg-[#4338CA]">Daftar</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-[1440px] px-space-md py-space-lg lg:px-margin">
        {{ $slot }}
    </main>
</body>
</html>
