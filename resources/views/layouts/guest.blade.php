{{--
    Layout halaman login, daftar, lupa password, dll.
    - Desktop: kiri panel biru (logo & nama sistem), kanan form.
    - HP: seluruh layar biru, form di kartu putih di tengah.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
        {{-- Izinkan transisi terbelah saat pindah ke halaman berikutnya (lihat resources/css/app.css) --}}
        <style>
            @media (prefers-reduced-motion: no-preference) {
                @view-transition { navigation: auto; }
            }
        </style>
    </head>
    <body class="bg-blue-600 font-sans text-slate-900 antialiased lg:bg-slate-50">
        <div class="lg:grid lg:min-h-screen lg:grid-cols-2">
            {{-- Kiri (desktop): identitas sistem di atas latar biru bergambar samar --}}
            <aside class="auth-left relative hidden overflow-hidden lg:block">
                @include('partials.auth-backdrop')

                <div class="relative flex h-full min-h-screen flex-col justify-between p-12 text-white xl:p-16">
                    <a href="{{ route('shop.home') }}" class="anim-fade self-start text-sm font-semibold text-blue-100 transition hover:text-white">
                        &larr; Lihat katalog produk
                    </a>

                    <div class="anim-rise max-w-lg">
                        <x-application-logo inverse class="h-20 w-20 rounded-2xl text-[2.5rem]" />
                        <h1 class="mt-8 text-5xl font-extrabold leading-[1.1] tracking-tight">{{ config('app.name') }}</h1>
                        <p class="mt-5 text-lg leading-relaxed text-blue-100">
                            Jual produk digital bersama para afiliator. Kelola produk, komisi, dan pembayaran dalam satu tempat.
                        </p>
                    </div>

                    <p class="text-sm text-blue-100/70">&copy; {{ now()->year }} {{ config('app.name') }}</p>
                </div>
            </aside>

            {{-- Kanan (desktop) / seluruh layar (HP): form --}}
            <main class="auth-right relative flex min-h-screen flex-col items-center justify-center px-5 py-10 sm:px-8 lg:bg-slate-50">
                {{-- HP: latar biru bergambar samar --}}
                <div class="lg:hidden">@include('partials.auth-backdrop')</div>

                <a href="{{ route('shop.home') }}" class="anim-fade relative mb-7 flex flex-col items-center text-center lg:hidden">
                    <x-application-logo inverse class="h-14 w-14 rounded-2xl text-[1.75rem]" />
                    <span class="mt-3 text-xl font-bold tracking-tight text-white">{{ config('app.name') }}</span>
                </a>

                <div class="anim-rise relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl shadow-blue-950/30 sm:p-8 lg:rounded-2xl lg:border lg:border-slate-200/80 lg:p-10 lg:shadow-xl lg:shadow-slate-900/5">
                    @if ($title)
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h2>
                    @endif
                    @if ($subtitle)
                        <p class="mt-1.5 text-sm text-slate-500">{{ $subtitle }}</p>
                    @endif

                    <div @class(['mt-7' => $title || $subtitle])>
                        {{ $slot }}
                    </div>
                </div>

                <p class="relative mt-7 text-center text-xs text-blue-100/80 lg:hidden">&copy; {{ now()->year }} {{ config('app.name') }}</p>
            </main>
        </div>
    </body>
</html>
