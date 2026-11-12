<?php

namespace Tests\Feature;

use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\WithdrawalStatus;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Hasil audit keamanan: setiap celah yang ditutup dijaga oleh satu test di sini.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TrustProxies::flushState();

        parent::tearDown();
    }

    private function product(): Product
    {
        return Product::create(['name' => 'Produk Uji', 'slug' => 'produk-uji', 'price' => 100000, 'commission_rate' => 40]);
    }

    // --- Header keamanan ---

    public function test_every_response_carries_security_headers(): void
    {
        foreach (['/', '/login', '/halaman-tidak-ada'] as $url) {
            $this->get($url)
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'")
                ->assertHeaderMissing('Strict-Transport-Security');
        }
    }

    public function test_https_requests_get_hsts_header(): void
    {
        $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_trusted_proxy_from_env_makes_forwarded_https_and_ip_count(): void
    {
        // Tanpa TRUSTED_PROXIES header X-Forwarded-* diabaikan
        $this->get('/login', ['X-Forwarded-Proto' => 'https'])->assertHeaderMissing('Strict-Transport-Security');

        config(['app.trusted_proxies' => '*']);
        (new AppServiceProvider($this->app))->boot();

        $this->get('/login', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.9'])
            ->assertHeader('Strict-Transport-Security');
        $this->assertSame('203.0.113.9', request()->ip());
    }

    // --- Halaman error ---

    public function test_error_pages_are_in_indonesian_and_never_show_exception_details(): void
    {
        $this->get('/halaman-tidak-ada')->assertNotFound()->assertSee('Halaman tidak ditemukan')->assertSee('Kembali ke beranda');

        // Pesan bawaan "No query results for model [App\Models\Product]" tidak boleh bocor
        $this->get('/products/slug-yang-tidak-ada')->assertNotFound()->assertDontSee('App\Models')->assertDontSee('No query results');

        $this->actingAs(User::factory()->customer()->create())->get('/admin')
            ->assertForbidden()->assertSee('Akses ditolak');
    }

    // --- Akun nonaktif ---

    public function test_deactivated_account_cannot_log_in(): void
    {
        $user = User::factory()->affiliate()->create(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => EnsureAccountIsActive::MESSAGE]);

        $this->assertGuest();
    }

    public function test_deactivated_account_is_logged_out_on_auth_only_pages(): void
    {
        $user = User::factory()->customer()->create();
        $this->actingAs($user)->get('/profile')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        // /profile hanya memakai middleware auth (tanpa role), tetap harus dikeluarkan
        $this->get('/profile')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => EnsureAccountIsActive::MESSAGE]);
        $this->assertGuest();
    }

    public function test_changing_password_ends_sessions_on_other_devices(): void
    {
        $user = User::factory()->customer()->create();
        $this->actingAs($user)->get('/profile')->assertOk();

        // Password diganti dari perangkat lain: sesi ini tidak lagi berlaku
        $user->forceFill(['password' => Hash::make('password-baru-123')])->save();

        $this->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // --- Lupa password ---

    public function test_forgot_password_does_not_reveal_whether_email_is_registered(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->post('/forgot-password', ['email' => $user->email]);
        $unknown = $this->post('/forgot-password', ['email' => 'tidak-terdaftar@contoh.test']);

        foreach ([$known, $unknown] as $response) {
            $response->assertSessionHasNoErrors()->assertSessionHas('status', 'Link reset password sudah dikirim ke email Anda.');
        }
        Notification::assertCount(1); // hanya email terdaftar yang benar-benar dikirimi link
    }

    // --- Pembatasan laju ---

    public function test_registration_is_rate_limited_per_ip(): void
    {
        foreach (range(1, 10) as $i) {
            $this->post('/register', ['name' => 'Bot', 'email' => "bot{$i}@contoh.test", 'password' => 'password123', 'password_confirmation' => 'password123'])
                ->assertRedirect();
            $this->post('/logout');
        }

        $this->post('/register', ['name' => 'Bot', 'email' => 'bot11@contoh.test', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertStatus(429);
        $this->assertDatabaseMissing('users', ['email' => 'bot11@contoh.test']);
    }

    public function test_referral_link_is_rate_limited_per_ip(): void
    {
        $affiliate = User::factory()->affiliate()->create();
        $affiliate->referralLinks()->create(['product_id' => $this->product()->id, 'code' => 'UJI123']);

        foreach (range(1, 60) as $i) {
            $this->get('/r/UJI123')->assertRedirect();
        }

        $this->get('/r/UJI123')->assertStatus(429);
    }

    // --- Unggahan ke folder publik ---

    public function test_text_promo_material_never_stores_an_uploaded_file(): void
    {
        Storage::fake('public');
        $product = $this->product();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.products.promo-materials.store', $product->id), [
                'title' => 'Caption',
                'type' => 'text',
                'caption' => 'Isi caption',
                'file' => UploadedFile::fake()->createWithContent('jahat.html', '<script>alert(1)</script>'),
            ])->assertSessionHasNoErrors();

        $this->assertNull($product->promoMaterials()->sole()->file_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_image_and_video_promo_materials_reject_other_file_types(): void
    {
        Storage::fake('public');
        $product = $this->product();
        $admin = User::factory()->admin()->create();

        foreach (['image', 'video'] as $type) {
            foreach (['jahat.html' => '<script>alert(1)</script>', 'jahat.php' => '<?php echo 1;', 'jahat.svg' => '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'] as $name => $content) {
                $this->actingAs($admin)
                    ->post(route('admin.products.promo-materials.store', $product->id), [
                        'title' => 'Materi', 'type' => $type, 'file' => UploadedFile::fake()->createWithContent($name, $content),
                    ])->assertSessionHasErrors('file');
            }
        }

        $this->assertSame(0, $product->promoMaterials()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_qris_image_must_be_a_browser_friendly_format(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.payment-accounts.store'), [
            'type' => 'qris', 'account_holder' => 'Toko', 'qris_image' => UploadedFile::fake()->image('qris.gif'),
        ])->assertSessionHasErrors('qris_image');

        $this->actingAs($admin)->post(route('admin.payment-accounts.store'), [
            'type' => 'qris', 'account_holder' => 'Toko', 'qris_image' => UploadedFile::fake()->image('qris.png'),
        ])->assertSessionHasNoErrors();
    }

    // --- Ekspor laporan ---

    public function test_report_export_neutralises_spreadsheet_formulas(): void
    {
        $customer = User::factory()->customer()->create(['name' => '=HYPERLINK("http://jahat.test","klik")', 'email' => 'pembeli@contoh.test']);
        Order::create([
            'invoice_number' => 'INV-CSV-1', 'customer_id' => $customer->id, 'product_id' => $this->product()->id,
            'amount' => 100000, 'payment_method' => PaymentMethod::Qris, 'status' => OrderStatus::Paid, 'verified_at' => now(),
        ]);

        $csv = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.reports.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('\'=HYPERLINK', $csv);
        $this->assertStringNotContainsString(';=HYPERLINK', $csv);
        $this->assertStringNotContainsString(';"=HYPERLINK', $csv);
    }

    // --- Keputusan admin tidak bisa diproses dua kali ---

    public function test_order_decision_is_final_once_made(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $affiliate = User::factory()->affiliate()->create();
        $product = $this->product();
        $link = $affiliate->referralLinks()->create(['product_id' => $product->id, 'code' => 'FINAL1']);
        $order = Order::create([
            'invoice_number' => 'INV-FINAL-1', 'customer_id' => User::factory()->customer()->create()->id, 'product_id' => $product->id,
            'referral_link_id' => $link->id, 'amount' => 100000, 'payment_method' => PaymentMethod::Qris,
            'status' => OrderStatus::WaitingVerification,
        ]);

        $this->actingAs($admin)->post(route('admin.orders.approve', $order))->assertSessionHas('status');
        $this->actingAs($admin)->post(route('admin.orders.reject', $order), ['rejection_note' => 'terlambat'])->assertSessionHas('error');
        $this->actingAs($admin)->post(route('admin.orders.approve', $order))->assertSessionHas('error');

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(1, Commission::where('order_id', $order->id)->count());
    }

    public function test_withdrawal_cannot_be_paid_twice(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $affiliate = User::factory()->affiliate()->create();
        $order = Order::create([
            'invoice_number' => 'INV-WD-1', 'customer_id' => User::factory()->customer()->create()->id, 'product_id' => $this->product()->id,
            'amount' => 100000, 'payment_method' => PaymentMethod::Qris, 'status' => OrderStatus::Paid,
        ]);
        Commission::create(['order_id' => $order->id, 'affiliate_id' => $affiliate->id, 'rate' => 40, 'amount' => 40000, 'status' => CommissionStatus::Approved]);
        $withdrawal = $affiliate->withdrawals()->create([
            'amount' => 30000, 'bank_name' => 'BCA', 'account_number' => '123', 'account_holder' => 'Budi', 'status' => WithdrawalStatus::Approved,
        ]);

        $pay = fn () => $this->actingAs($admin)->post(route('admin.withdrawals.pay', $withdrawal), [
            'transfer_proof' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $pay()->assertSessionHas('status');
        $pay()->assertSessionHas('error');

        $this->assertSame(WithdrawalStatus::Paid, $withdrawal->fresh()->status);
        $this->assertCount(1, Storage::disk('local')->allFiles('withdrawal-proofs')); // tidak ada bukti kedua yang tertinggal
    }
}
