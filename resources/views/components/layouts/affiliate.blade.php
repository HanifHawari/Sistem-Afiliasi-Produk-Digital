@props(['title' => null])

{{-- Layout area Afiliator  —  PIC: Gibran Muhammad Ardan --}}
<x-layouts.dashboard :title="$title" area="Ruang Afiliator">
    <x-slot name="nav">
        <x-sidebar-heading>Navigasi</x-sidebar-heading>
        <x-sidebar-link :href="route('affiliate.dashboard')" icon="analytics" :active="request()->routeIs('affiliate.dashboard')">Ringkasan Afiliasi</x-sidebar-link>
        <x-sidebar-link :href="route('affiliate.products.index')" icon="storefront" :active="request()->routeIs('affiliate.products.*')">Katalog Produk</x-sidebar-link>
        <x-sidebar-link :href="route('affiliate.links.index')" icon="link" :active="request()->routeIs('affiliate.links.*')">Link &amp; Kampanye</x-sidebar-link>
        <x-sidebar-link :href="route('affiliate.promo-materials.index')" icon="perm_media" :active="request()->routeIs('affiliate.promo-materials.*')">Materi Promosi</x-sidebar-link>

        <x-sidebar-heading>Keuangan</x-sidebar-heading>
        <x-sidebar-link :href="route('affiliate.withdrawals.index')" icon="account_balance_wallet" :active="request()->routeIs('affiliate.withdrawals.*')">Penarikan Komisi</x-sidebar-link>
        <x-sidebar-link :href="route('affiliate.bank-account.edit')" icon="account_balance" :active="request()->routeIs('affiliate.bank-account.*')">Rekening Bank</x-sidebar-link>
    </x-slot>

    {{ $slot }}
</x-layouts.dashboard>
