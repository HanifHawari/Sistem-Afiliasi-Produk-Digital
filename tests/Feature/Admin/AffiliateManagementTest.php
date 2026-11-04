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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $affiliate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->affiliate = User::factory()->affiliate()->create(['name' => 'Budi Uji']);

        // 4 klik, 1 penjualan Rp 100.000, komisi Rp 40.000, pencairan Rp 15.000
        $product = Product::create(['name' => 'Ebook Uji', 'slug' => 'ebook-uji', 'price' => 100000, 'commission_rate' => 40]);
        $link = $this->affiliate->referralLinks()->create(['product_id' => $product->id, 'code' => 'BUDIUJI']);
        foreach (range(1, 4) as $i) {
            $link->clicks()->create(['clicked_at' => now()]);
        }
        $order = Order::create([
            'invoice_number' => 'INV-AF', 'customer_id' => User::factory()->customer()->create()->id,
            'product_id' => $product->id, 'referral_link_id' => $link->id, 'amount' => 100000,
            'payment_method' => PaymentMethod::Qris, 'status' => OrderStatus::Paid,
        ]);
        Commission::create([
            'order_id' => $order->id, 'affiliate_id' => $this->affiliate->id,
            'rate' => 40, 'amount' => 40000, 'status' => CommissionStatus::Approved,
        ]);
        $this->affiliate->withdrawals()->create([
            'amount' => 15000, 'bank_name' => 'BCA', 'account_number' => '1', 'account_holder' => 'Budi',
            'status' => WithdrawalStatus::Paid,
        ]);
    }

    public function test_index_shows_performance_numbers(): void
    {
        $this->actingAs($this->admin)->get(route('admin.affiliates.index'))
            ->assertOk()
            ->assertSee('Budi Uji')
            ->assertSee('4 klik')
            ->assertSee('1 terjual')
            ->assertSee('konversi 25,0%')
            ->assertSee('Saldo Rp'."\u{00A0}".'25.000', false);
    }

    public function test_index_filters_and_search(): void
    {
        User::factory()->affiliate()->create(['name' => 'Reza Nonaktif', 'is_active' => false]);

        $this->actingAs($this->admin)->get(route('admin.affiliates.index', ['status' => 'inactive']))
            ->assertSee('Reza Nonaktif')->assertDontSee('Budi Uji');

        $this->actingAs($this->admin)->get(route('admin.affiliates.index', ['q' => 'budi', 'sort' => 'sales']))
            ->assertSee('Budi Uji')->assertDontSee('Reza Nonaktif');

        $this->actingAs($this->admin)->get(route('admin.affiliates.index', ['status' => 'ngawur', 'sort' => 'ngawur']))->assertOk();
    }

    public function test_detail_page(): void
    {
        $this->actingAs($this->admin)->get(route('admin.affiliates.show', $this->affiliate))
            ->assertOk()
            ->assertSee('Performa per Produk')
            ->assertSee('BUDIUJI')
            ->assertSee('INV-AF');
    }

    public function test_detail_of_non_affiliate_is_not_found(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($this->admin)->get(route('admin.affiliates.show', $customer))->assertNotFound();
        $this->actingAs($this->admin)->patch(route('admin.affiliates.toggle', $customer))->assertNotFound();
    }

    public function test_deactivated_affiliate_cannot_use_dashboard(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.affiliates.toggle', $this->affiliate))->assertSessionHas('status');
        $this->assertFalse($this->affiliate->refresh()->is_active);

        $this->actingAs($this->affiliate)->get('/affiliate')->assertRedirect(route('login'));

        $this->actingAs($this->admin)->patch(route('admin.affiliates.toggle', $this->affiliate));
        $this->assertTrue($this->affiliate->refresh()->is_active);
    }
}
