<x-layouts.shop title="Checkout & Pembayaran">
    <x-page-header eyebrow="Checkout" title="Checkout & Pembayaran" description="Slug produk: {{ $product }}" />

    <x-placeholder owner="Muhammad Arif Bagus" branch="feature/storefront-checkout" design="design/stitch/arif_storefront-checkout">
        <li>Pilih metode: QRIS atau transfer bank (tabel payment_accounts)</li>
        <li>Kode referral: ReferralTracker::resolveForCheckout()</li>
        <li>Buat order + upload bukti bayar (disk local/privat)</li>
        <li>Status order: pending -> waiting_verification</li>
    </x-placeholder>
</x-layouts.shop>
