<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ isset($title) && $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

<!-- Font & ikon sesuai desain Stitch -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">

{{-- Transisi pintu setelah login/daftar: ditandai sebelum halaman tampil supaya tidak berkedip (lihat resources/js/app.js) --}}
<script>
    try {
        if (sessionStorage.getItem('afp-door')) {
            sessionStorage.removeItem('afp-door');
            if (! matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.documentElement.classList.add('door-opening');
            }
        }
    } catch (e) {}
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
