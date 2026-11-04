<?php

namespace Tests\Feature\Admin;

use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\WithdrawalStatus;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Commission\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WithdrawalProcessingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $affiliate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->affiliate = User::factory()->affiliate()->create();

        // Afiliator punya komisi Rp 40.000
        $product = Product::create(['name' => 'Produk', 'slug' => 'produk', 'price' => 100000, 'commission_rate' => 40]);
        $order = Order::create([
            'invoice_number' => 'INV-1', 'customer_id' => User::factory()->customer()->create()->id,
            'product_id' => $product->id, 'amount' => 100000, 'payment_method' => PaymentMethod::Qris,
            'status' => OrderStatus::Paid,
        ]);
        Commission::create([
            'order_id' => $order->id, 'affiliate_id' => $this->affiliate->id,
            'rate' => 40, 'amount' => 40000, 'status' => CommissionStatus::Approved,
        ]);
    }

    private function withdrawal(int $amount, WithdrawalStatus $status = WithdrawalStatus::Pending): Withdrawal
    {
        return $this->affiliate->withdrawals()->create([
            'amount' => $amount, 'bank_name' => 'BCA', 'account_number' => '123', 'account_holder' => 'Budi',
            'status' => $status,
        ]);
    }

    public function test_page_shows_balance_check(): void
    {
        $withdrawal = $this->withdrawal(25000);

        $this->actingAs($this->admin)
            ->get(route('admin.withdrawals.index', ['withdrawal' => $withdrawal->id]))
            ->assertOk()
            ->assertSee('Cek Saldo Komisi Afiliator')
            ->assertSee('Setujui Pengajuan');
    }

    public function test_full_flow_approve_then_pay(): void
    {
        Storage::fake('local');
        $withdrawal = $this->withdrawal(25000);

        $this->actingAs($this->admin)->post(route('admin.withdrawals.approve', $withdrawal))->assertSessionHas('status');
        $this->assertSame(WithdrawalStatus::Approved, $withdrawal->refresh()->status);

        $this->actingAs($this->admin)
            ->post(route('admin.withdrawals.pay', $withdrawal), [])
            ->assertSessionHasErrors('transfer_proof');

        $this->actingAs($this->admin)->post(route('admin.withdrawals.pay', $withdrawal), [
            'transfer_proof' => UploadedFile::fake()->image('bukti.jpg'),
            'note' => 'Ref 123',
        ])->assertSessionHas('status');

        $withdrawal->refresh();
        $this->assertSame(WithdrawalStatus::Paid, $withdrawal->status);
        $this->assertSame($this->admin->id, $withdrawal->processed_by);
        Storage::disk('local')->assertExists($withdrawal->transfer_proof);

        $this->actingAs($this->admin)->get(route('admin.withdrawals.proof', $withdrawal))->assertOk();
        $this->actingAs($this->affiliate)->get(route('admin.withdrawals.proof', $withdrawal))->assertForbidden();
    }

    public function test_cannot_approve_when_balance_insufficient(): void
    {
        $this->withdrawal(30000, WithdrawalStatus::Paid);
        $withdrawal = $this->withdrawal(20000); // total 50.000 > komisi 40.000

        $this->actingAs($this->admin)->post(route('admin.withdrawals.approve', $withdrawal))->assertSessionHas('error');
        $this->assertSame(WithdrawalStatus::Pending, $withdrawal->refresh()->status);
    }

    public function test_cannot_pay_before_approval(): void
    {
        $withdrawal = $this->withdrawal(10000);

        $this->actingAs($this->admin)->post(route('admin.withdrawals.pay', $withdrawal), [
            'transfer_proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHas('error');

        $this->assertSame(WithdrawalStatus::Pending, $withdrawal->refresh()->status);
    }

    public function test_reject_requires_reason_and_restores_balance(): void
    {
        $withdrawal = $this->withdrawal(40000);

        $this->actingAs($this->admin)->post(route('admin.withdrawals.reject', $withdrawal), ['note' => ''])
            ->assertSessionHasErrors('note');

        $this->actingAs($this->admin)->post(route('admin.withdrawals.reject', $withdrawal), ['note' => 'Rekening tidak valid'])
            ->assertSessionHas('status');

        $this->assertSame(WithdrawalStatus::Rejected, $withdrawal->refresh()->status);
        $this->assertSame(40000, app(CommissionService::class)->balanceFor($this->affiliate));
    }

    public function test_paid_withdrawal_cannot_be_rejected(): void
    {
        $withdrawal = $this->withdrawal(10000, WithdrawalStatus::Paid);

        $this->actingAs($this->admin)->post(route('admin.withdrawals.reject', $withdrawal), ['note' => 'Salah'])
            ->assertSessionHas('error');

        $this->assertSame(WithdrawalStatus::Paid, $withdrawal->refresh()->status);
    }
}
