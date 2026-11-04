{{-- Tombol edit / aktif-nonaktif / hapus untuk satu QRIS atau rekening --}}
<div class="flex flex-wrap items-center justify-end gap-2">
    <button type="button" class="btn-soft" @click="edit(@js($toJs($account)))">Edit</button>

    <form method="POST" action="{{ route('admin.payment-accounts.toggle', $account) }}">
        @csrf @method('PATCH')
        <button @class(['btn btn-sm', 'bg-red-50 text-red-600 hover:bg-red-100' => $account->is_active, 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' => ! $account->is_active])>
            {{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
        </button>
    </form>

    @if ($account->orders_count === 0)
        <form method="POST" action="{{ route('admin.payment-accounts.destroy', $account) }}" onsubmit="return confirm('Hapus metode pembayaran ini?')">
            @csrf @method('DELETE')
            <button class="btn-outline btn-sm">Hapus</button>
        </form>
    @endif
</div>
