<?php

namespace Tests\Feature\Affiliate;

use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\WithdrawalStatus;
use App\Models\AffiliateProfile;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Commission\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private CommissionService $commissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commissionService = app(CommissionService::class);
    }

    private function addCommission(User $affiliate, int $amount): Commission
    {
        static $counter = 1;
        $customer = User::factory()->customer()->create();
        $product = Product::create([
            'name' => 'Produk Digital Test ' . $counter,
            'slug' => 'produk-digital-test-' . $counter,
            'price' => $amount,
            'commission_rate' => 100,
        ]);
        $order = Order::create([
            'invoice_number' => 'INV-TEST-WITHDRAWAL-' . $counter++,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'amount' => $amount,
            'payment_method' => PaymentMethod::Qris,
            'status' => OrderStatus::Paid,
        ]);

        return Commission::create([
            'order_id' => $order->id,
            'affiliate_id' => $affiliate->id,
            'rate' => 100,
            'amount' => $amount,
            'status' => CommissionStatus::Approved,
        ]);
    }

    private function setBankProfile(User $user, string $bank = 'BCA', string $account = '1234567890', string $holder = 'Budi Santoso'): AffiliateProfile
    {
        return AffiliateProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'bank_name' => $bank,
                'account_number' => $account,
                'account_holder' => $holder,
            ],
        );
    }

    public function test_guest_cannot_access_withdrawals_page(): void
    {
        $this->get(route('affiliate.withdrawals.index'))
            ->assertRedirect(route('login'));

        $this->post(route('affiliate.withdrawals.store'), ['amount' => 50000])
            ->assertRedirect(route('login'));
    }

    public function test_non_affiliate_user_cannot_access_withdrawals_page(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($admin)
            ->get(route('affiliate.withdrawals.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('affiliate.withdrawals.store'), ['amount' => 50000])
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('affiliate.withdrawals.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('affiliate.withdrawals.store'), ['amount' => 50000])
            ->assertForbidden();
    }

    public function test_affiliate_can_view_withdrawals_page(): void
    {
        $affiliate = User::factory()->affiliate()->create();
        $this->setBankProfile($affiliate, 'Mandiri', '9876543210', 'Ahmad Dani');
        $this->addCommission($affiliate, 75000);

        $response = $this->actingAs($affiliate)->get(route('affiliate.withdrawals.index'));

        $response->assertOk()
            ->assertSee('Penarikan Komisi')
            ->assertSee(rupiah(75000))
            ->assertSee('Mandiri');
    }

    public function test_affiliate_cannot_request_withdrawal_without_complete_bank_account(): void
    {
        $affiliate = User::factory()->affiliate()->create();
        $this->addCommission($affiliate, 100000);

        // Kasus 1: Belum ada profil sama sekali
        $response1 = $this->actingAs($affiliate)->post(route('affiliate.withdrawals.store'), [
            'amount' => 50000,
        ]);
        $response1->assertSessionHasErrors('amount');
        $this->assertDatabaseEmpty('withdrawals');

        // Kasus 2: Profil ada tetapi kolom rekening tidak lengkap
        AffiliateProfile::create([
            'user_id' => $affiliate->id,
            'bank_name' => 'BCA',
            'account_number' => '',
            'account_holder' => null,
        ]);

        $response2 = $this->actingAs($affiliate)->post(route('affiliate.withdrawals.store'), [
            'amount' => 50000,
        ]);
        $response2->assertSessionHasErrors('amount');
        $this->assertDatabaseEmpty('withdrawals');
    }

    public function test_invalid_amounts_are_rejected(): void
    {
        $affiliate = User::factory()->affiliate()->create();
        $this->setBankProfile($affiliate);
        $this->addCommission($affiliate, 100000);

        $invalidInputs = [
            'zero' => 0,
            'negative' => -10000,
            'decimal' => 15000.75,
            'string' => 'lima_puluh_ribu',
            'empty' => '',
        ];

        foreach ($invalidInputs as $scenario => $value) {
            $response = $this->actingAs($affiliate)->post(route('affiliate.withdrawals.store'), [
                'amount' => $value,
            ]);

            $response->assertSessionHasErrors('amount');
        }

        $this->assertDatabaseEmpty('withdrawals');
    }

    public function test_request_exceeding_available_balance_is_rejected(): void
    {
        $affiliate = User::factory()->affiliate()->create();
        $this->setBankProfile($affiliate);
        $this->addCommission($affiliate, 50000);

        $response = $this->actingAs($affiliate)->post(route('affiliate.withdrawals.store'), [
            'amount' => 50001,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseEmpty('withdrawals');
        $this->assertSame(50000, $this->commissionService->balanceFor($affiliate));
    }

    public function test_valid_withdrawal_request_is_created_successfully(): void
    {
        $affiliate = User::factory()->affiliate()->create();
        $this->setBankProfile($affiliate, 'BCA', '1122334455', 'Siti Aminah');
        $this->addCommission($affiliate, 100000);

        $response = $this->actingAs($affiliate)->post(route('affiliate.withdrawals.store'), [
            'amount' => 45000,
        ]);

        $response->assertRedirect(route('affiliate.withdrawals.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('withdrawals', [
            'affiliate_id' => $affiliate->id,
            'amount' => 45000,
            'bank_name' => 'BCA',
            'account_number' => '1122334455',
            'account_holder' => 'Siti Aminah',
            'status' => WithdrawalStatus::Pending->value,
        ]);
    }

    public function test_new_withdrawal_status_is_pending(): void
    {
        $affiliate = User::factory()->affiliate()->create();
        $this->setBankProfile($affiliate);
        $this->addCommission($affiliate, 50000);

        $this->actingAs($affiliate)->post(route('affiliate.withdrawals.store'), [
            'amount' => 20000,
        ]);

        $withdrawal = $affiliate->withdrawals()->first();
        $this->assertNotNull($withdrawal);
        $this->assertSame(WithdrawalStatus::Pending, $withdrawal->status);
    }

    public function test_snapshot_bank_details_saved_from_profile_at_request_time(): void
    {
        $affiliate = User::factory()->affiliate()->create();
        $profile = $this->setBankProfile($affiliate, 'Bank Mandiri', '1234567890', 'Pemilik Asli');
        $this->addCommission($affiliate, 80000);

        $this->actingAs($affiliate)->post(route('affiliate.withdrawals.store'), [
            'amount' => 30000,
        ]);

        $withdrawal = $affiliate->withdrawals()->first();
        $this->assertSame('Bank Mandiri', $withdrawal->bank_name);
        $this->assertSame('1234567890', $withdrawal->account_number);
        $this->assertSame('Pemilik Asli', $withdrawal->account_holder);

        // Ubah profil afiliator setelah pengajuan dibuat
        $profile->update([
            'bank_name' => 'Bank BCA',
            'account_number' => '9999999999',
            'account_holder' => 'Nama Baru',
        ]);

        // Snapshot pada pengajuan lama tidak boleh ikut berubah
        $withdrawal->refresh();
        $this->assertSame('Bank Mandiri', $withdrawal->bank_name);
        $this->assertSame('1234567890', $withdrawal->account_number);
        $this->assertSame('Pemilik Asli', $withdrawal->account_holder);
    }

    public function test_available_balance_decreases_immediately_after_withdrawal_request(): void
    {
        $affiliate = User::factory()->affiliate()->create();
        $this->setBankProfile($affiliate);
        $this->addCommission($affiliate, 100000);

        $this->assertSame(100000, $this->commissionService->balanceFor($affiliate));

        $this->actingAs($affiliate)->post(route('affiliate.withdrawals.store'), [
            'amount' => 35000,
        ]);

        $this->assertSame(65000, $this->commissionService->balanceFor($affiliate));
    }

    public function test_rejected_withdrawal_restores_available_balance(): void
    {
        $affiliate = User::factory()->affiliate()->create();
        $this->setBankProfile($affiliate);
        $this->addCommission($affiliate, 100000);

        $this->actingAs($affiliate)->post(route('affiliate.withdrawals.store'), [
            'amount' => 40000,
        ]);

        $this->assertSame(60000, $this->commissionService->balanceFor($affiliate));

        $withdrawal = $affiliate->withdrawals()->first();
        $withdrawal->update([
            'status' => WithdrawalStatus::Rejected,
            'note' => 'Rekening tidak valid.',
        ]);

        $this->assertSame(100000, $this->commissionService->balanceFor($affiliate));
    }

    public function test_affiliate_only_sees_their_own_withdrawal_history(): void
    {
        $affiliate1 = User::factory()->affiliate()->create(['name' => 'Afiliator Satu']);
        $affiliate2 = User::factory()->affiliate()->create(['name' => 'Afiliator Dua']);

        $this->setBankProfile($affiliate1, 'BCA', '1111111111', 'Afiliator Satu');
        $this->setBankProfile($affiliate2, 'BRI', '2222222222', 'Afiliator Dua');

        $this->addCommission($affiliate1, 100000);
        $this->addCommission($affiliate2, 100000);

        // Afiliator 1 punya withdrawal Rp 25.000
        $affiliate1->withdrawals()->create([
            'amount' => 25000,
            'bank_name' => 'BCA',
            'account_number' => '1111111111',
            'account_holder' => 'Afiliator Satu',
            'status' => WithdrawalStatus::Pending,
        ]);

        // Afiliator 2 punya withdrawal Rp 45.000
        $affiliate2->withdrawals()->create([
            'amount' => 45000,
            'bank_name' => 'BRI',
            'account_number' => '2222222222',
            'account_holder' => 'Afiliator Dua',
            'status' => WithdrawalStatus::Pending,
        ]);

        // Afiliator 1 membuka halaman penarikan
        $response1 = $this->actingAs($affiliate1)->get(route('affiliate.withdrawals.index'));
        $response1->assertOk()
            ->assertSee(rupiah(25000))
            ->assertDontSee(rupiah(45000))
            ->assertDontSee('2222222222');

        // Afiliator 2 membuka halaman penarikan
        $response2 = $this->actingAs($affiliate2)->get(route('affiliate.withdrawals.index'));
        $response2->assertOk()
            ->assertSee(rupiah(45000))
            ->assertDontSee(rupiah(25000))
            ->assertDontSee('1111111111');
    }

    public function test_sequential_requests_cannot_overdraw_balance_into_negative(): void
    {
        $affiliate = User::factory()->affiliate()->create();
        $this->setBankProfile($affiliate);
        $this->addCommission($affiliate, 50000);

        // Request 1: Rp 30.000 -> berhasil, saldo tersisa Rp 20.000
        $this->actingAs($affiliate)->post(route('affiliate.withdrawals.store'), [
            'amount' => 30000,
        ])->assertSessionHas('status');

        $this->assertSame(20000, $this->commissionService->balanceFor($affiliate));

        // Request 2: Rp 30.000 -> harus ditolak karena saldo hanya Rp 20.000
        $this->actingAs($affiliate)->post(route('affiliate.withdrawals.store'), [
            'amount' => 30000,
        ])->assertSessionHasErrors('amount');

        // Pastikan hanya 1 penarikan yang tercatat dan saldo tidak negatif
        $this->assertCount(1, $affiliate->withdrawals);
        $this->assertSame(20000, $this->commissionService->balanceFor($affiliate));
        $this->assertGreaterThanOrEqual(0, $this->commissionService->balanceFor($affiliate));
    }
}
