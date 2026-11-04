<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_with_orders_cannot_delete_account(): void
    {
        $customer = User::factory()->customer()->create();
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 10000, 'commission_rate' => 10]);
        Order::create([
            'invoice_number' => 'INV-A', 'customer_id' => $customer->id, 'product_id' => $product->id,
            'amount' => 10000, 'payment_method' => PaymentMethod::Qris,
        ]);

        $this->actingAs($customer)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertModelExists($customer);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/profile')->assertOk()->assertDontSee('Hapus Akun');
        $this->actingAs($admin)->delete('/profile', ['password' => 'password'])->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertModelExists($admin);
    }

    public function test_profile_page_uses_role_layout(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/profile')->assertOk()->assertSee('Panel Admin');
        $this->actingAs(User::factory()->affiliate()->create())->get('/profile')->assertOk()->assertSee('Ruang Afiliator');
        $this->actingAs(User::factory()->customer()->create())->get('/profile')->assertOk()->assertSee('Pesanan Saya');
    }

    public function test_invalid_filters_are_ignored_without_redirect(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/orders?status=ngawur&order=abc')->assertOk();
        $this->actingAs($admin)->get('/admin/withdrawals?status=ngawur&withdrawal=abc')->assertOk();
    }

    public function test_validation_messages_are_in_indonesian(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.products.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Nama produk wajib diisi.']);
    }

    public function test_product_without_orders_can_be_deleted(): void
    {
        $product = Product::create(['name' => 'Hapus Saya', 'slug' => 'hapus-saya', 'price' => 10000, 'commission_rate' => 10]);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.products.destroy', $product->id))
            ->assertSessionHas('status');

        $this->assertModelMissing($product);
    }
}
