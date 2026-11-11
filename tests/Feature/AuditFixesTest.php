<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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

    public function test_array_query_params_do_not_crash_admin_pages(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([
            '/admin/orders?q[]=x&status[]=y&order[]=1',
            '/admin/withdrawals?q[]=x&status[]=y&withdrawal[]=1',
            '/admin/products?q[]=x',
            '/admin/affiliates?q[]=x&sort[]=y&status[]=z',
            '/admin/reports?preset[]=x&start[]=a&end[]=b',
            '/admin/reports?start=2025-13-45&end=2025-02-30',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
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

    public function test_seeder_creates_only_team_accounts_and_removes_leftover_uploads(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('local')->put('payment-proofs/INV-LAMA-0001.png', 'sisa seed lama');
        Storage::disk('public')->put('avatars/lama.jpg', 'sisa unggahan lama');

        $this->seed();

        $this->assertSame(
            ['bagus@afiliasi.test', 'fadhil@afiliasi.test', 'gibran@afiliasi.test', 'hanif@afiliasi.test'],
            User::orderBy('email')->pluck('email')->all(),
        );
        Storage::disk('local')->assertMissing('payment-proofs/INV-LAMA-0001.png');
        Storage::disk('public')->assertMissing('avatars/lama.jpg');
        Storage::disk('public')->assertExists('qris/contoh-qris.png');
        foreach (Order::whereNotNull('payment_proof')->pluck('payment_proof') as $proof) {
            Storage::disk('local')->assertExists($proof);
        }

        // Setiap produk contoh punya file yang bisa diunduh pembeli setelah lunas
        foreach (Product::all() as $product) {
            $this->assertNotNull($product->file_path, $product->name.' tidak punya file');
            Storage::disk('local')->assertExists($product->file_path);
            $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($product->file_path));
        }

        $paid = Order::where('status', 'paid')->where('customer_id', User::firstWhere('email', 'bagus@afiliasi.test')->id)->first();
        $this->actingAs($paid->customer)
            ->get(URL::temporarySignedRoute('shop.orders.download', now()->addMinutes(10), ['order' => $paid]))
            ->assertOk()
            ->assertDownload();
    }

    public function test_password_fields_have_show_password_button(): void
    {
        $this->get('/login')->assertOk()->assertSee('Tampilkan password');
        $this->get('/register')->assertOk()->assertSee('Tampilkan password');

        $this->actingAs(User::factory()->admin()->create())
            ->get('/profile')->assertOk()->assertSee('Tampilkan password');
    }
}
