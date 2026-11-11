<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_each_role_to_its_home(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')->assertRedirect(route('admin.dashboard'));

        $this->actingAs(User::factory()->affiliate()->create())
            ->get('/dashboard')->assertRedirect(route('affiliate.dashboard'));

        $this->actingAs(User::factory()->customer()->create())
            ->get('/dashboard')->assertRedirect(route('shop.orders.index'));
    }

    public function test_admin_pages_only_for_admin(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->affiliate()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->customer()->create())->get('/admin')->assertForbidden();

        $admin = User::factory()->admin()->create();
        foreach (['/admin', '/admin/products', '/admin/affiliates', '/admin/orders', '/admin/withdrawals', '/admin/payment-accounts', '/admin/reports'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_affiliate_pages_only_for_affiliate(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/affiliate')->assertForbidden();

        $affiliate = User::factory()->affiliate()->create();
        foreach (['/affiliate', '/affiliate/products', '/affiliate/links', '/affiliate/promo-materials', '/affiliate/withdrawals', '/affiliate/bank-account'] as $url) {
            $this->actingAs($affiliate)->get($url)->assertOk();
        }
    }

    public function test_customer_pages(): void
    {
        Product::create([
            'name' => 'Contoh Produk',
            'slug' => 'contoh-produk',
            'price' => 100000,
            'commission_rate' => 30,
        ]);

        $this->get('/')->assertOk();
        $this->get('/products/contoh-produk')->assertOk();
        $this->get('/orders')->assertRedirect(route('login'));

        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->get('/orders')->assertOk();
        $this->actingAs($customer)->get('/checkout/contoh-produk')->assertOk();
    }

    public function test_inactive_user_is_logged_out(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->forceFill(['is_active' => false])->save();

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_referral_link_records_click_and_sets_cookie(): void
    {
        $affiliate = User::factory()->affiliate()->create();
        $product = Product::create([
            'name' => 'Produk Uji', 'slug' => 'produk-uji', 'price' => 100000, 'commission_rate' => 40,
        ]);
        $link = $affiliate->referralLinks()->create(['product_id' => $product->id, 'code' => 'UJI123']);

        $this->get('/r/UJI123?ch=tiktok')
            ->assertRedirect(route('shop.products.show', $product))
            ->assertCookie('ref', 'UJI123');

        $this->assertDatabaseHas('referral_clicks', ['referral_link_id' => $link->id, 'channel' => 'tiktok']);
        $this->get('/r/TIDAKADA')->assertNotFound();
    }
}
