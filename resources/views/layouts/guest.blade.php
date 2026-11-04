<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-surface font-body-md text-body-md text-on-surface antialiased">
        <div class="flex min-h-screen flex-col items-center px-space-md pt-space-2xl sm:justify-center sm:pt-0">
            <a href="{{ route('shop.home') }}" class="flex items-center gap-space-sm">
                <x-application-logo class="h-11 w-11 text-lg" />
                <span class="font-headline-md text-headline-md text-primary">{{ config('app.name') }}</span>
            </a>

            <div class="mt-space-lg w-full overflow-hidden rounded-xl bg-surface-container-lowest p-space-lg shadow-sm sm:max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
