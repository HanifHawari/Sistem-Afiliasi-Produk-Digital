import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/*
 * Transisi masuk (login / daftar):
 * 1. Form dengan atribut data-intro: tombol berubah "Memproses...", penanda disimpan di sessionStorage.
 * 2. <head> halaman berikutnya (partials/head) membaca penanda -> kelas .intro + izin View Transition.
 * 3. Di sini: beri urutan (--i) ke menu samping & kartu, lalu jalankan animasinya (.intro-run).
 * Gaya & animasi: resources/css/app.css.
 */
const root = document.documentElement;

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form.matches('form[data-intro]') || event.defaultPrevented) {
        return;
    }

    try {
        sessionStorage.setItem('afp-intro', '1');
    } catch (e) {
        // sessionStorage tidak tersedia: tetap login, hanya tanpa animasi
    }

    const button = form.querySelector('[type="submit"]');
    if (button) {
        button.dataset.label = button.innerHTML;
        button.innerHTML = '<span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span> Memproses...';
        button.disabled = true;
    }
});

// Kembali lewat tombol Back (halaman dari cache browser): pulihkan tombol
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) {
        return;
    }
    document.querySelectorAll('form[data-intro] [data-label]').forEach((button) => {
        button.innerHTML = button.dataset.label;
        button.disabled = false;
    });
});

if (root.classList.contains('intro')) {
    if (window.matchMedia('(min-width: 1024px)').matches) {
        document.querySelectorAll('[data-intro-group="left"] > *, [data-intro="left"]')
            .forEach((el, i) => el.style.setProperty('--i', Math.min(i, 12)));

        document.querySelectorAll('[data-intro="up"], main .card')
            .forEach((el, i) => el.style.setProperty('--i', Math.min(i, 10)));
    }

    root.classList.add('intro-run');
    setTimeout(() => root.classList.remove('intro', 'intro-run'), 3000);
}
