@props(['title' => null])

{{-- Layout area Admin  —  PIC: Muhammad Hanif Hawari --}}
<x-layouts.dashboard :title="$title" area="Panel Admin">
    <x-slot name="nav">
        <x-sidebar-heading>Ringkasan</x-sidebar-heading>
        <x-sidebar-link :href="route('admin.dashboard')" icon="dashboard" :active="request()->routeIs('admin.dashboard')">Dashboard</x-sidebar-link>
        <x-sidebar-link :href="route('admin.reports.index')" icon="monitoring" :active="request()->routeIs('admin.reports.*')">Laporan Penjualan</x-sidebar-link>

        <x-sidebar-heading>Transaksi</x-sidebar-heading>
        <x-sidebar-link :href="route('admin.orders.index')" icon="verified" :active="request()->routeIs('admin.orders.*')">Verifikasi Pembayaran</x-sidebar-link>
        <x-sidebar-link :href="route('admin.withdrawals.index')" icon="account_balance_wallet" :active="request()->routeIs('admin.withdrawals.*')">Pencairan Komisi</x-sidebar-link>

        <x-sidebar-heading>Kelola</x-sidebar-heading>
        <x-sidebar-link :href="route('admin.products.index')" icon="category" :active="request()->routeIs('admin.products.*')">Produk &amp; Komisi</x-sidebar-link>
        <x-sidebar-link :href="route('admin.affiliates.index')" icon="group" :active="request()->routeIs('admin.affiliates.*')">Afiliator</x-sidebar-link>
        <x-sidebar-link :href="route('admin.payment-accounts.index')" icon="qr_code_2" :active="request()->routeIs('admin.payment-accounts.*')">QRIS &amp; Rekening</x-sidebar-link>
    </x-slot>

    {{ $slot }}
</x-layouts.dashboard>
