import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/*
 * Transisi "pintu" saat masuk (lihat resources/views/partials/page-door.blade.php).
 * 1. Form dengan atribut data-door: pintu menutup dulu, baru form dikirim.
 * 2. Penanda di sessionStorage dibaca oleh <head> halaman berikutnya (partials/head) -> pintu membuka.
 */
const root = document.documentElement;
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form.matches('form[data-door]') || event.defaultPrevented || reduceMotion) {
        return;
    }

    event.preventDefault();
    try {
        sessionStorage.setItem('afp-door', '1');
    } catch (e) {
        // sessionStorage tidak tersedia (mode privat tertentu): tetap kirim, tanpa animasi membuka
    }
    root.classList.add('door-closing');
    setTimeout(() => form.submit(), 650);
});

// Kembali lewat tombol Back (halaman dari cache browser): buka lagi pintunya
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        root.classList.remove('door-closing');
    }
});

// Setelah pintu terbuka penuh, lepas penandanya
if (root.classList.contains('door-opening')) {
    const done = () => root.classList.remove('door-opening');
    document.querySelector('.page-door-b')?.addEventListener('animationend', (event) => {
        if (event.animationName === 'door-open') {
            done();
        }
    });
    setTimeout(done, 2500);
}
