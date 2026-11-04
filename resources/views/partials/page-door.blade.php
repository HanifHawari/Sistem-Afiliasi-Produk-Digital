{{--
    Transisi "pintu" saat masuk (login/daftar):
    - Form dengan atribut data-door: pintu biru menutup, lalu form dikirim (resources/js/app.js).
    - Halaman berikutnya: garis cahaya muncul di tengah, lalu pintu membelah dan terbuka.
    Desktop: pintu kiri-kanan. HP: pintu atas-bawah. Gaya & animasi ada di resources/css/app.css.
--}}
<div class="page-door" aria-hidden="true">
    <div class="page-door-half page-door-a"><div class="page-door-canvas">@include('partials.auth-backdrop')</div></div>
    <div class="page-door-half page-door-b"><div class="page-door-canvas">@include('partials.auth-backdrop')</div></div>
    <span class="page-door-seam"></span>
    <div class="page-door-brand">
        <x-application-logo inverse class="h-16 w-16 rounded-2xl text-[2rem]" />
        <span class="mt-4 text-xl font-bold tracking-tight text-white">{{ config('app.name') }}</span>
    </div>
</div>
