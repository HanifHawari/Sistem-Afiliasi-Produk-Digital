<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReferralLink;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Commission\CommissionService;
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
            ['atmind@afiliasi.test', 'bagus@afiliasi.test', 'fadhil@afiliasi.test', 'gibran@afiliasi.test'],
            User::orderBy('email')->pluck('email')->all(),
        );
        Storage::disk('local')->assertMissing('payment-proofs/INV-LAMA-0001.png');
        Storage::disk('public')->assertMissing('avatars/lama.jpg');
        Storage::disk('public')->assertExists('qris/contoh-qris.png');
        foreach (Order::whereNotNull('payment_proof')->pluck('payment_proof') as $proof) {
            Storage::disk('local')->assertExists($proof);
        }

        // Sembilan produk contoh (katalog 3 kolom terisi penuh), semuanya dipromosikan Gibran
        $this->assertSame(9, Product::count());
        $this->assertSame(9, User::firstWhere('email', 'gibran@afiliasi.test')->referralLinks()->count());

        // Gambar sampul dipasang dari database/seeders/sampul/{slug}.*; produk tanpa file di sana tetap tanpa gambar
        foreach (Product::all() as $product) {
            $sample = glob(database_path("seeders/sampul/{$product->slug}.*"));

            if ($sample === []) {
                $this->assertNull($product->thumbnail, $product->name.' seharusnya belum punya gambar sampul');

                continue;
            }

            Storage::disk('public')->assertExists($product->thumbnail);
            [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($product->thumbnail));
            $this->assertEqualsWithDelta(1.6, $width / $height, 0.01, $product->name.' tidak berasio 16:10');
            $this->assertLessThanOrEqual(1600, $width);
        }
        $this->assertNotNull(Product::firstWhere('slug', 'mastering-tiktok-organic-affiliate-2025')->thumbnail);

        // Data contoh harus masuk akal: penjualan tiap link tidak melebihi kliknya (konversi maksimal 100%),
        // setiap pencairan contoh memenuhi minimal Rp 50.000, dan tidak ada saldo afiliator yang negatif
        foreach (ReferralLink::withCount('clicks')->withCount(['orders as paid_count' => fn ($q) => $q->where('status', 'paid')])->get() as $link) {
            $this->assertLessThanOrEqual($link->clicks_count, $link->paid_count, "Link {$link->code}: penjualan melebihi klik");
        }
        $this->assertGreaterThanOrEqual(50000, Withdrawal::min('amount'));
        foreach (User::where('role', 'affiliate')->get() as $affiliate) {
            $this->assertGreaterThanOrEqual(0, app(CommissionService::class)->balanceFor($affiliate), "Saldo {$affiliate->email} negatif");
        }

        // Setiap produk contoh punya deskripsi dan file yang bisa diunduh pembeli setelah lunas
        foreach (Product::all() as $product) {
            $this->assertNotEmpty($product->description, $product->name.' tidak punya deskripsi');
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
