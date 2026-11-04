@props(['title' => null])

{{--
    Layout area Admin  —  PIC: Muhammad Hanif Hawari
    $pendingOrders & $pendingWithdrawals dikirim oleh view composer di AppServiceProvider.
--}}
<x-layouts.dashboard :title="$title" area="Panel Admin">
    <x-slot name="headerActions">
        <a href="{{ route('admin.orders.index', ['status' => 'waiting_verification']) }}"
           class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-surface-container-low text-on-surface-variant hover:bg-surface-container"
           title="{{ $pendingOrders }} pembayaran menunggu verifikasi">
            <span class="material-symbols-outlined text-[22px]">notifications</span>
            @if ($pendingOrders > 0)
                <span class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full border-2 border-surface-container-low bg-error"></span>
            @endif
        </a>
    </x-slot>

    <x-slot name="nav">
        <x-sidebar-heading>Ringkasan</x-sidebar-heading>
        <x-sidebar-link :href="route('admin.dashboard')" icon="space_dashboard" :active="request()->routeIs('admin.dashboard')">Dashboard</x-sidebar-link>
        <x-sidebar-link :href="route('admin.reports.index')" icon="monitoring" :active="request()->routeIs('admin.reports.*')">Laporan Penjualan</x-sidebar-link>

        <x-sidebar-heading>Transaksi</x-sidebar-heading>
        <x-sidebar-link :href="route('admin.orders.index')" icon="verified" :active="request()->routeIs('admin.orders.*')" :count="$pendingOrders">Pembayaran Masuk</x-sidebar-link>
        <x-sidebar-link :href="route('admin.withdrawals.index')" icon="account_balance_wallet" :active="request()->routeIs('admin.withdrawals.*')" :count="$pendingWithdrawals">Pencairan Komisi</x-sidebar-link>

        <x-sidebar-heading>Kelola</x-sidebar-heading>
        <x-sidebar-link :href="route('admin.products.index')" icon="category" :active="request()->routeIs('admin.products.*')">Produk &amp; Komisi</x-sidebar-link>
        <x-sidebar-link :href="route('admin.affiliates.index')" icon="group" :active="request()->routeIs('admin.affiliates.*')">Afiliator</x-sidebar-link>
        <x-sidebar-link :href="route('admin.payment-accounts.index')" icon="qr_code_2" :active="request()->routeIs('admin.payment-accounts.*')">QRIS &amp; Rekening</x-sidebar-link>
    </x-slot>

    <x-slot name="sidebarFooter">
        <a href="{{ route('admin.orders.index', ['status' => 'waiting_verification']) }}"
           class="flex items-center justify-between rounded-xl bg-surface-container-low p-space-sm hover:bg-surface-container">
            <div class="flex flex-col">
                <span class="font-caption text-caption text-on-surface-variant">Perlu Tindakan</span>
                @if ($pendingOrders + $pendingWithdrawals > 0)
                    <span class="flex items-center gap-1.5 font-label-sm text-label-sm text-tertiary">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-tertiary"></span>
                        {{ $pendingOrders }} pembayaran · {{ $pendingWithdrawals }} pencairan
                    </span>
                @else
                    <span class="flex items-center gap-1.5 font-label-sm text-label-sm text-secondary">
                        <span class="h-2 w-2 rounded-full bg-secondary"></span>
                        Semua beres
                    </span>
                @endif
            </div>
            <span class="material-symbols-outlined text-[18px] text-outline">pending_actions</span>
        </a>
    </x-slot>

    {{ $slot }}
</x-layouts.dashboard>
