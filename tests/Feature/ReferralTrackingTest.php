<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReferralLink;
use App\Models\User;
use App\Services\Commission\CommissionService;
use App\Services\Referral\ReferralStatistics;
use App\Services\Referral\ReferralTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReferralTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $affiliate;

    private User $customer;

    private Product $product;

    private ReferralLink $link;

    protected function setUp(): void
    {
        parent::setUp();
        $this->affiliate = User::factory()->affiliate()->create();
        $this->customer = User::factory()->customer()->create();
        $this->product = Product::create([
            'name' => 'Produk Referral', 'slug' => 'produk-referral',
            'price' => 100001, 'commission_rate' => 12.5,
        ]);
        $this->link = $this->affiliate->referralLinks()->create([
            'product_id' => $this->product->id, 'code' => 'TESTREF',
        ]);
    }

    private function checkout(array $input = [], mixed $cookie = null, ?User $user = null): ?ReferralLink
    {
        $request = Request::create('/checkout', 'POST', $input, ['ref' => $cookie]);
        $request->setUserResolver(fn () => $user ?? $this->customer);

        return app(ReferralTracker::class)->resolveForCheckout($request, $this->product);
    }

    private function order(OrderStatus $status = OrderStatus::Paid, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'invoice_number' => 'INV-'.Order::count(), 'customer_id' => $this->customer->id,
            'product_id' => $this->product->id, 'referral_link_id' => $this->link->id,
            'amount' => 100001, 'payment_method' => PaymentMethod::BankTransfer, 'status' => $status,
        ], $attributes));
    }

    public function test_redirect_records_metadata_and_sets_thirty_day_cookie(): void
    {
        $this->freezeTime();
        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1', 'HTTP_USER_AGENT' => 'Referral Browser'])
            ->get('/r/TESTREF?utm_source=instagram');
        $response->assertRedirect(route('shop.products.show', $this->product))
            ->assertCookie('ref', 'TESTREF');
        $this->assertDatabaseHas('referral_clicks', [
            'referral_link_id' => $this->link->id, 'ip_address' => '192.0.2.1',
            'user_agent' => 'Referral Browser', 'channel' => 'instagram',
        ]);
        $cookie = app(ReferralTracker::class)->makeCookie($this->link);
        $this->assertSame(now()->addDays(30)->timestamp, $cookie->getExpiresTime());
        $this->assertTrue($cookie->isHttpOnly());
    }

    public function test_repeated_clicks_are_deduplicated_only_within_five_minutes(): void
    {
        $this->get('/r/TESTREF?ch=tiktok');
        $this->get('/r/TESTREF?ch=instagram');
        $this->assertDatabaseCount('referral_clicks', 1);
        $this->travel(6)->minutes();
        $this->get('/r/TESTREF');
        $this->assertDatabaseCount('referral_clicks', 2);
    }

    public function test_encrypted_cookie_is_resolved_at_checkout_and_latest_link_wins(): void
    {
        // Route khusus test untuk menyimulasikan checkout yang belum dibangun Arif.
        Route::middleware('web')->post('/test-referral-checkout', function (Request $request, ReferralTracker $tracker) {
            return response()->json(['link_id' => $tracker->resolveForCheckout($request, $this->product)?->id]);
        });
        $other = User::factory()->affiliate()->create()->referralLinks()->create([
            'product_id' => $this->product->id, 'code' => 'LATEST',
        ]);
        $this->get('/r/TESTREF')->assertCookie('ref', 'TESTREF');
        $response = $this->get('/r/LATEST');
        $response->assertCookie('ref', 'LATEST');
        $encrypted = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'ref');
        $this->actingAs($this->customer)->withUnencryptedCookie('ref', $encrypted->getValue())
            ->post('/test-referral-checkout')->assertOk()->assertJson(['link_id' => $other->id]);
    }

    public function test_other_visitors_and_links_are_counted_separately(): void
    {
        $this->get('/r/TESTREF');
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Other browser'])->get('/r/TESTREF');
        $other = User::factory()->affiliate()->create()->referralLinks()->create([
            'product_id' => $this->product->id, 'code' => 'OTHER',
        ]);
        $this->get('/r/'.$other->code);
        $this->assertDatabaseCount('referral_clicks', 3);
    }

    public function test_channel_is_bounded_and_malformed_query_is_safe(): void
    {
        $this->get('/r/TESTREF?ch='.str_repeat('a', 50).'&utm_source=ignored')->assertRedirect();
        $this->assertSame(str_repeat('a', 30), $this->link->clicks()->first()->channel);
        $this->travel(6)->minutes();
        $this->get('/r/TESTREF?ch[]=invalid')->assertRedirect();
        $this->assertNull($this->link->clicks()->latest('id')->first()->channel);
    }

    public function test_unknown_inactive_or_non_affiliate_links_do_not_track_or_set_cookie(): void
    {
        $this->get('/r/MISSING')->assertNotFound()->assertCookieMissing('ref');
        $this->affiliate->forceFill(['is_active' => false])->save();
        $this->get('/r/TESTREF')->assertNotFound()->assertCookieMissing('ref');
        $this->affiliate->forceFill(['is_active' => true, 'role' => 'customer'])->save();
        $this->get('/r/TESTREF')->assertNotFound()->assertCookieMissing('ref');
        $this->affiliate->forceFill(['role' => 'affiliate'])->save();
        $this->product->update(['is_active' => false]);
        $this->get('/r/TESTREF')->assertNotFound()->assertCookieMissing('ref');
        $this->assertDatabaseCount('referral_clicks', 0);
    }

    public function test_checkout_uses_manual_code_then_cookie_and_rejects_invalid_manual_code(): void
    {
        $this->assertSame($this->link->id, $this->checkout(['referral_code' => ' TESTREF '], 'UNKNOWN')?->id);
        $this->assertSame($this->link->id, $this->checkout([], 'TESTREF')?->id);
        $this->assertSame($this->link->id, $this->checkout(['referral_code' => ' '], 'TESTREF')?->id);
        $this->assertNull($this->checkout(['referral_code' => 'UNKNOWN'], 'TESTREF'));
        $this->assertNull($this->checkout());
        $this->assertNull($this->checkout(['referral_code' => ['TESTREF']], 'TESTREF'));
        $this->assertNull($this->checkout([], ['TESTREF']));
        $this->assertNull($this->checkout(['referral_code' => str_repeat('a', 33)]));
    }

    public function test_checkout_rejects_self_referrals_inactive_affiliates_and_wrong_products(): void
    {
        $this->assertNull($this->checkout([], 'TESTREF', $this->affiliate));
        $this->affiliate->forceFill(['is_active' => false])->save();
        $this->assertNull($this->checkout([], 'TESTREF'));
        $this->affiliate->forceFill(['is_active' => true, 'role' => 'customer'])->save();
        $this->assertNull($this->checkout([], 'TESTREF'));
        $this->affiliate->forceFill(['role' => 'affiliate'])->save();
        $otherProduct = Product::create(['name' => 'Other', 'slug' => 'other', 'price' => 100, 'commission_rate' => 10]);
        $this->link->update(['product_id' => $otherProduct->id]);
        $this->assertNull($this->checkout([], 'TESTREF'));
        $this->link->update(['product_id' => $this->product->id]);
        $this->product->update(['is_active' => false]);
        $this->assertNull($this->checkout([], 'TESTREF'));
    }

    public function test_commission_requires_persisted_paid_status_and_referral(): void
    {
        $service = app(CommissionService::class);
        foreach ([OrderStatus::Pending, OrderStatus::WaitingVerification, OrderStatus::Rejected] as $status) {
            $order = $this->order($status);
            $order->status = OrderStatus::Paid;
            $this->assertNull($service->createForOrder($order));
        }
        $this->assertNull($service->createForOrder($this->order(attributes: ['referral_link_id' => null])));
        $this->assertNull($service->createForOrder(new Order));
        $this->assertDatabaseCount('commissions', 0);
    }

    public function test_commission_rounds_and_is_idempotent_after_rate_or_affiliate_changes(): void
    {
        $service = app(CommissionService::class);
        $order = $this->order();
        $commission = $service->createForOrder($order);
        $this->assertSame(12500, $commission->amount);
        $this->assertSame('12.50', $commission->rate);
        $this->product->update(['commission_rate' => 50]);
        $this->affiliate->forceFill(['is_active' => false])->save();
        $again = $service->createForOrder($order);
        $this->assertSame($commission->id, $again->id);
        $this->assertSame(12500, $again->amount);
        $this->assertDatabaseCount('commissions', 1);
    }

    public function test_commission_uses_fresh_status_and_survives_product_deactivation_after_purchase(): void
    {
        $order = $this->order(OrderStatus::Pending);
        Order::whereKey($order->id)->update(['status' => OrderStatus::Paid]);
        $this->product->update(['is_active' => false]);
        $commission = app(CommissionService::class)->createForOrder($order);
        $this->assertNotNull($commission);
        $this->assertSame(12500, $commission->amount);
    }

    public function test_commission_rechecks_affiliate_and_prevents_self_referral(): void
    {
        $service = app(CommissionService::class);
        $order = $this->order();
        $order->load('referralLink.affiliate');
        $this->affiliate->forceFill(['is_active' => false])->save();
        $this->assertNull($service->createForOrder($order));
        $this->affiliate->forceFill(['is_active' => true, 'role' => 'customer'])->save();
        $this->assertNull($service->createForOrder($order));
        $this->affiliate->forceFill(['role' => 'affiliate'])->save();
        $order->update(['customer_id' => $this->affiliate->id]);
        $this->assertNull($service->createForOrder($order));
        $this->assertDatabaseCount('commissions', 0);
    }

    public function test_commission_rejects_product_mismatch_and_invalid_percentage(): void
    {
        $service = app(CommissionService::class);
        $other = Product::create(['name' => 'Other', 'slug' => 'other', 'price' => 100, 'commission_rate' => 10]);
        $this->assertNull($service->createForOrder($this->order(attributes: ['product_id' => $other->id])));
        $this->product->update(['commission_rate' => 101]);
        $this->assertNull($service->createForOrder($this->order()));
        $this->assertDatabaseCount('commissions', 0);
    }

    public function test_statistics_only_include_owned_links_and_paid_conversions(): void
    {
        $this->link->clicks()->createMany([
            ['clicked_at' => now()], ['clicked_at' => now()],
        ]);
        $paid = $this->order();
        $this->order(OrderStatus::Pending);
        $this->order(OrderStatus::Rejected);
        app(CommissionService::class)->createForOrder($paid);
        $otherAffiliate = User::factory()->affiliate()->create();
        $otherLink = $otherAffiliate->referralLinks()->create(['product_id' => $this->product->id, 'code' => 'OTHER']);
        $otherLink->clicks()->create(['clicked_at' => now()]);
        $this->order(attributes: ['referral_link_id' => $otherLink->id]);
        $bank = ['bank_name' => 'BCA', 'account_number' => '123', 'account_holder' => 'Affiliate'];
        $this->affiliate->withdrawals()->create($bank + ['amount' => 2000, 'status' => 'pending']);
        $this->affiliate->withdrawals()->create($bank + ['amount' => 5000, 'status' => 'rejected']);

        $stats = app(ReferralStatistics::class)->summaryFor($this->affiliate);
        $this->assertSame([
            'clicks' => 2, 'orders' => 3, 'paid_orders' => 1, 'conversion_rate' => 50.0,
            'revenue' => 100001, 'commission' => 12500, 'balance' => 10500,
        ], $stats);
        $links = app(ReferralStatistics::class)->linksFor($this->affiliate);
        $this->assertCount(1, $links);
        $this->assertSame(2, $links->first()->clicks_count);
        $this->assertSame(1, $links->first()->paid_orders_count);
        $this->assertSame(50.0, $links->first()->conversion_rate);
    }

    public function test_statistics_with_no_activity_return_zero(): void
    {
        $summary = app(ReferralStatistics::class)->summaryFor($this->affiliate);
        $this->assertSame(0.0, $summary['conversion_rate']);
        $this->assertSame(0, $summary['balance']);
        $this->assertSame(0, $summary['paid_orders']);
    }
}
