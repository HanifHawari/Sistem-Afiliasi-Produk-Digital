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

{{--
    Animasi masuk halaman, ditandai sebelum halaman tampil supaya tidak berkedip (lihat resources/css/app.css):
    - .intro : halaman pertama setelah login/daftar (+ izin transisi terbelah dari halaman login)
    - .enter : pindah dari halaman lain. Tidak dipasang bila halamannya sama (filter, paginasi, setelah aksi)
               atau lewat tombol Back/Forward.
--}}
<script>
    try {
        const root = document.documentElement;
        const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
        const loginAt = Number(sessionStorage.getItem('afp-intro'));

        if (loginAt) {
            sessionStorage.removeItem('afp-intro');
            // Penanda hanya berlaku sebentar setelah tombol Masuk/Daftar ditekan
            if (! calm && Date.now() - loginAt < 20000) {
                root.classList.add('intro');
                const style = document.createElement('style');
                style.textContent = '@view-transition { navigation: auto; }';
                document.head.appendChild(style);
            }
        } else if (! calm) {
            const samePage = document.referrer !== '' && new URL(document.referrer).pathname === location.pathname;
            const fromHistory = performance.getEntriesByType('navigation')[0]?.type === 'back_forward';
            if (! samePage && ! fromHistory) {
                root.classList.add('enter');
            }
        }

        // Pengaman: bila resources/js/app.js gagal dimuat, animasi tetap jalan supaya konten tidak tersembunyi
        if (root.classList.contains('intro') || root.classList.contains('enter')) {
            setTimeout(() => root.classList.add('anim-run'), 1000);
        }
    } catch (e) {}
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
