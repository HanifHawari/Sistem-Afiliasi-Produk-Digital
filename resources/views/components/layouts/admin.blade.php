@props(['title' => null])

{{--
    Layout area Admin  —  PIC: Muhammad Hanif Hawari
    $pendingOrders & $pendingWithdrawals dikirim oleh view composer di AppServiceProvider.
--}}
<x-layouts.dashboard :title="$title" area="Panel Admin">
    <x-slot name="headerActions">
        <a href="{{ route('admin.orders.index', ['status' => 'waiting_verification']) }}"
           class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 lg:h-11 lg:w-11"
           title="{{ $pendingOrders }} pembayaran menunggu verifikasi">
            <span class="material-symbols-outlined icon-light text-[22px]">notifications</span>
            @if ($pendingOrders > 0)
                <span class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full border-2 border-white bg-red-500"></span>
            @endif
        </a>
    </x-slot>

    <x-slot name="nav">
        <x-sidebar-heading>Ringkasan</x-sidebar-heading>
        <x-sidebar-link :href="route('admin.dashboard')" icon="home" :active="request()->routeIs('admin.dashboard')">Dashboard</x-sidebar-link>
        <x-sidebar-link :href="route('admin.reports.index')" icon="bar_chart" :active="request()->routeIs('admin.reports.*')">Laporan Penjualan</x-sidebar-link>

        <x-sidebar-heading>Transaksi</x-sidebar-heading>
        <x-sidebar-link :href="route('admin.orders.index')" icon="credit_card" :active="request()->routeIs('admin.orders.*')" :count="$pendingOrders">Pembayaran Masuk</x-sidebar-link>
        <x-sidebar-link :href="route('admin.withdrawals.index')" icon="account_balance_wallet" :active="request()->routeIs('admin.withdrawals.*')" :count="$pendingWithdrawals">Pencairan Komisi</x-sidebar-link>

        <x-sidebar-heading>Kelola</x-sidebar-heading>
        <x-sidebar-link :href="route('admin.products.index')" icon="deployed_code" :active="request()->routeIs('admin.products.*')">Produk &amp; Komisi</x-sidebar-link>
        <x-sidebar-link :href="route('admin.affiliates.index')" icon="group" :active="request()->routeIs('admin.affiliates.*')">Afiliator</x-sidebar-link>
        <x-sidebar-link :href="route('admin.payment-accounts.index')" icon="qr_code_2" :active="request()->routeIs('admin.payment-accounts.*')">QRIS &amp; Rekening</x-sidebar-link>
    </x-slot>

    <x-slot name="sidebarFooter">
        <a href="{{ route('admin.orders.index', ['status' => 'waiting_verification']) }}"
           class="block rounded-xl border border-blue-100 bg-blue-50/40 px-4 py-4 transition hover:bg-blue-50">
            <p class="text-sm font-semibold text-slate-800">Perlu Tindakan</p>
            <p class="mt-1 text-xs text-blue-600">
                @if ($pendingOrders + $pendingWithdrawals > 0)
                    {{ $pendingOrders }} pembayaran &bull; {{ $pendingWithdrawals }} pencairan
                @else
                    Semua beres
                @endif
            </p>
        </a>
    </x-slot>

    {{ $slot }}
</x-layouts.dashboard>
