<x-layouts.admin title="Verifikasi Pembayaran">
    <x-page-header eyebrow="Transaksi" title="Verifikasi Pembayaran" description="Tinjau bukti pembayaran QRIS/transfer lalu setujui atau tolak." />

    <x-placeholder owner="Muhammad Hanif Hawari" branch="feature/admin-dashboard" design="design/stitch/hanif_admin-dashboard_verifikasi-pembayaran">
        <li>Tab status: menunggu verifikasi, berhasil, ditolak</li>
        <li>Lihat bukti bayar (file privat di disk local)</li>
        <li>Setujui: status jadi paid, panggil CommissionService::createForOrder()</li>
        <li>Tolak: status rejected + isi rejection_note</li>
    </x-placeholder>
</x-layouts.admin>
