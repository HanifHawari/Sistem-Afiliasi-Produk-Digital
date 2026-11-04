<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Commission\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->product = Product::create(['name' => 'Kelas Uji', 'slug' => 'kelas-uji', 'price' => 100000, 'commission_rate' => 40]);
    }

    private function paidOrder(int $daysAgo, bool $viaAffiliate = false, OrderStatus $status = OrderStatus::Paid): Order
    {
        $link = null;
        if ($viaAffiliate) {
            $affiliate = User::factory()->affiliate()->create(['name' => 'Afiliator Laporan']);
            $link = $affiliate->referralLinks()->create(['product_id' => $this->product->id, 'code' => 'LAP'.uniqid()]);
        }

        $order = Order::create([
            'invoice_number' => 'INV-LAP-'.uniqid(),
            'customer_id' => User::factory()->customer()->create()->id,
            'product_id' => $this->product->id,
            'referral_link_id' => $link?->id,
            'amount' => 100000,
            'payment_method' => PaymentMethod::Qris,
            'status' => $status,
            'verified_at' => now()->subDays($daysAgo),
        ]);
        app(CommissionService::class)->createForOrder($order);

        return $order;
    }

    public function test_summary_counts_only_paid_orders_in_range(): void
    {
        $this->paidOrder(1, viaAffiliate: true);        // omzet 100rb, komisi 40rb
        $this->paidOrder(2);                            // omzet 100rb, organik
        $this->paidOrder(40);                           // di luar 30 hari
        $this->paidOrder(1, status: OrderStatus::WaitingVerification); // belum dibayar

        $response = $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();

        $response->assertViewHas('summary', fn ($s) => $s['revenue'] === 200000
            && $s['orders'] === 2
            && $s['commission'] === 40000
            && $s['owner'] === 160000
            && $s['affiliate_revenue'] === 100000);

        $response->assertSee('Kelas Uji')->assertSee('Afiliator Laporan');
    }

    public function test_compares_with_previous_period(): void
    {
        $this->paidOrder(3);
        $this->paidOrder(10); // periode 7 hari sebelumnya

        $this->actingAs($this->admin)->get(route('admin.reports.index', ['preset' => '7d']))
            ->assertOk()
            ->assertViewHas('previous', fn ($p) => $p['revenue'] === 100000)
            ->assertSee('+0,0% vs periode sebelumnya');
    }

    public function test_custom_range_and_invalid_input(): void
    {
        $this->paidOrder(40);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['start' => now()->subDays(45)->toDateString(), 'end' => now()->toDateString()]))
            ->assertOk()
            ->assertViewHas('preset', 'custom')
            ->assertViewHas('summary', fn ($s) => $s['orders'] === 1);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['start' => 'kemarin', 'end' => '2026-99-99', 'preset' => 'ngawur']))
            ->assertOk()
            ->assertViewHas('preset', '30d');
    }

    public function test_empty_period_shows_empty_state(): void
    {
        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Belum ada penjualan di periode ini');
    }

    public function test_export_csv(): void
    {
        $order = $this->paidOrder(1, viaAffiliate: true);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.export'))->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Invoice;"Tanggal Verifikasi"', $csv);
        $this->assertStringContainsString($order->invoice_number, $csv);
        $this->assertStringContainsString(';100000;40000;60000', $csv);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_only_admin_can_see_report(): void
    {
        $this->actingAs(User::factory()->affiliate()->create())->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs(User::factory()->affiliate()->create())->get(route('admin.reports.export'))->assertForbidden();
    }
}
