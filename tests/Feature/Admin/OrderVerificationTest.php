<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $affiliate;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->affiliate = User::factory()->affiliate()->create();
        $this->product = Product::create([
            'name' => 'Ebook Uji', 'slug' => 'ebook-uji', 'price' => 100000, 'commission_rate' => 40,
        ]);
    }

    private function makeOrder(bool $withReferral = true, OrderStatus $status = OrderStatus::WaitingVerification): Order
    {
        $link = $withReferral
            ? $this->affiliate->referralLinks()->firstOrCreate(['product_id' => $this->product->id], ['code' => 'UJI123'])
            : null;

        return Order::create([
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'customer_id' => User::factory()->customer()->create()->id,
            'product_id' => $this->product->id,
            'referral_link_id' => $link?->id,
            'amount' => 100000,
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => $status,
        ]);
    }

    public function test_orders_page_renders_with_inspector(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->invoice_number)
            ->assertSee('Verifikasi &amp; Buka Akses Produk', false);
    }

    public function test_search_filters_orders(): void
    {
        $order = $this->makeOrder();
        $other = $this->makeOrder();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['status' => 'all', 'q' => $order->invoice_number]))
            ->assertOk()
            ->assertSee($order->invoice_number)
            ->assertDontSee($other->invoice_number);
    }

    public function test_approve_marks_paid_and_creates_commission(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.approve', $order))
            ->assertRedirect()
            ->assertSessionHas('status');

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame($this->admin->id, $order->verified_by);
        $this->assertDatabaseHas('commissions', [
            'order_id' => $order->id,
            'affiliate_id' => $this->affiliate->id,
            'amount' => 40000,
        ]);
    }

    public function test_approve_without_referral_creates_no_commission(): void
    {
        $order = $this->makeOrder(withReferral: false);

        $this->actingAs($this->admin)->post(route('admin.orders.approve', $order));

        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
        $this->assertDatabaseCount('commissions', 0);
    }

    public function test_approving_twice_does_not_duplicate_commission(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin)->post(route('admin.orders.approve', $order));
        $this->actingAs($this->admin)->post(route('admin.orders.approve', $order))->assertSessionHas('error');

        $this->assertDatabaseCount('commissions', 1);
    }

    public function test_reject_requires_note_and_sets_status(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.reject', $order), ['rejection_note' => ''])
            ->assertSessionHasErrors('rejection_note');

        $this->actingAs($this->admin)
            ->post(route('admin.orders.reject', $order), ['rejection_note' => 'Nominal tidak sesuai'])
            ->assertSessionHas('status');

        $order->refresh();
        $this->assertSame(OrderStatus::Rejected, $order->status);
        $this->assertSame('Nominal tidak sesuai', $order->rejection_note);
        $this->assertDatabaseCount('commissions', 0);
    }

    public function test_payment_proof_is_private_to_admin(): void
    {
        Storage::fake('local');
        $order = $this->makeOrder();
        $order->update(['payment_proof' => UploadedFile::fake()->image('bukti.png')->store('payment-proofs', 'local')]);

        $this->actingAs($this->admin)->get(route('admin.orders.proof', $order))->assertOk();
        $this->actingAs($order->customer)->get(route('admin.orders.proof', $order))->assertForbidden();
    }
}
