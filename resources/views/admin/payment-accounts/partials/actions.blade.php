{{-- Tombol edit / aktif-nonaktif / hapus untuk satu QRIS atau rekening --}}
<div class="flex items-center justify-end gap-1">
    <button type="button" title="Edit" class="rounded-lg p-1.5 text-primary transition-colors hover:bg-primary-fixed"
            @click="edit(@js($toJs($account)))">
        <span class="material-symbols-outlined text-[18px]">edit</span>
    </button>

    <form method="POST" action="{{ route('admin.payment-accounts.toggle', $account) }}">
        @csrf @method('PATCH')
        <button title="{{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                @class(['rounded-lg p-1.5 transition-colors', 'text-error hover:bg-error-container' => $account->is_active, 'text-secondary hover:bg-secondary-fixed' => ! $account->is_active])>
            <span class="material-symbols-outlined text-[18px]">power_settings_new</span>
        </button>
    </form>

    @if ($account->orders_count === 0)
        <form method="POST" action="{{ route('admin.payment-accounts.destroy', $account) }}" onsubmit="return confirm('Hapus metode pembayaran ini?')">
            @csrf @method('DELETE')
            <button title="Hapus" class="rounded-lg p-1.5 text-on-surface-variant transition-colors hover:bg-error-container hover:text-error">
                <span class="material-symbols-outlined text-[18px]">delete</span>
            </button>
        </form>
    @endif
</div>
