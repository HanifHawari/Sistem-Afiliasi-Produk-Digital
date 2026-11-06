<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Enums\PaymentAccountType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\ReferralLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StorefrontCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $otherCustomer;

    private User $affiliate;

    private Product $product;

    private Product $otherProduct;

    private PaymentAccount $qrisAccount;

    private PaymentAccount $bankAccount;

    private ReferralLink $validReferral;

    private ReferralLink $otherProductReferral;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->customer()->create();
        $this->otherCustomer = User::factory()->customer()->create();
        $this->affiliate = User::factory()->affiliate()->create();

        $this->product = Product::create([
            'name' => 'Mastering TikTok Affiliate',
            'slug' => 'mastering-tiktok-affiliate',
            'category' => 'E-Course',
            'description' => 'Panduan lengkap TikTok Affiliate.',
            'price' => 150000,
            'commission_rate' => 40,
            'file_path' => 'products/files/mastering-tiktok.pdf',
            'is_active' => true,
        ]);

        $this->otherProduct = Product::create([
            'name' => 'Template Notion Freelancer',
            'slug' => 'template-notion-freelancer',
            'category' => 'Template',
            'price' => 99000,
            'commission_rate' => 50,
            'is_active' => true,
        ]);

        $this->qrisAccount = PaymentAccount::create([
            'type' => PaymentAccountType::Qris,
            'account_holder' => 'Afiliasi Produk Digital',
            'is_active' => true,
        ]);

        $this->bankAccount = PaymentAccount::create([
            'type' => PaymentAccountType::Bank,
            'bank_name' => 'BCA',
            'account_number' => '8091129900',
            'account_holder' => 'PT Afiliasi Produk Digital',
            'is_active' => true,
        ]);

        $this->validReferral = $this->affiliate->referralLinks()->create([
            'product_id' => $this->product->id,
            'code' => 'BUDI1',
        ]);

        $this->otherProductReferral = $this->affiliate->referralLinks()->create([
            'product_id' => $this->otherProduct->id,
            'code' => 'BUDI2',
        ]);
    }

    public function test_checkout_creates_pending_order_with_product_price_snapshot(): void
    {
        $response = $this->actingAs($this->customer)
            ->post(route('shop.checkout.store', $this->product), [
                'payment_method' => PaymentMethod::Qris->value,
            ]);

        $order = Order::first();

        $this->assertNotNull($order);
        $response->assertRedirect(route('shop.orders.pay', $order));
        $this->assertSame($this->customer->id, $order->customer_id);
        $this->assertSame($this->product->id, $order->product_id);
        $this->assertSame(150000, $order->amount);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentMethod::Qris, $order->payment_method);
        $this->assertSame($this->qrisAccount->id, $order->payment_account_id);
        $this->assertMatchesRegularExpression('/^INV-\d{8}-[A-Z0-9]{4}$/', $order->invoice_number);
    }

    public function test_valid_referral_is_attached_while_other_product_or_self_referral_is_ignored(): void
    {
        // 1. Referral valid untuk produk yang sama -> terisi
        $this->actingAs($this->customer)
            ->post(route('shop.checkout.store', $this->product), [
                'payment_method' => PaymentMethod::Qris->value,
                'referral_code' => $this->validReferral->code,
            ])
            ->assertSessionHasNoErrors();

        $orderWithValidRef = Order::sole();
        $this->assertSame($this->validReferral->id, $orderWithValidRef->referral_link_id);

        Order::query()->delete();

        // 2. Referral milik produk lain -> diabaikan diam-diam tanpa error
        $this->actingAs($this->customer)
            ->post(route('shop.checkout.store', $this->product), [
                'payment_method' => PaymentMethod::Qris->value,
                'referral_code' => $this->otherProductReferral->code,
            ])
            ->assertSessionHasNoErrors();

        $orderWithOtherProductRef = Order::sole();
        $this->assertNull($orderWithOtherProductRef->referral_link_id);

        Order::query()->delete();

        // 3. Referral milik diri sendiri (self-referral) -> diabaikan diam-diam tanpa error
        $selfReferral = $this->customer->referralLinks()->create([
            'product_id' => $this->product->id,
            'code' => 'SELF123',
        ]);

        $this->actingAs($this->customer)
            ->post(route('shop.checkout.store', $this->product), [
                'payment_method' => PaymentMethod::Qris->value,
                'referral_code' => $selfReferral->code,
            ])
            ->assertSessionHasNoErrors();

        $orderWithSelfRef = Order::sole();
        $this->assertNull($orderWithSelfRef->referral_link_id);
    }

    public function test_other_customer_cannot_access_someone_elses_order(): void
    {
        Storage::fake('local');

        $order = Order::create([
            'invoice_number' => 'INV-20261105-ABCD',
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'amount' => $this->product->price,
            'payment_method' => PaymentMethod::Qris,
            'payment_account_id' => $this->qrisAccount->id,
            'status' => OrderStatus::Pending,
        ]);

        $showResponse = $this->actingAs($this->otherCustomer)
            ->get(route('shop.orders.show', $order));
        $this->assertContains($showResponse->getStatusCode(), [403, 404]);

        $payResponse = $this->actingAs($this->otherCustomer)
            ->get(route('shop.orders.pay', $order));
        $this->assertContains($payResponse->getStatusCode(), [403, 404]);

        $proofResponse = $this->actingAs($this->otherCustomer)
            ->post(route('shop.orders.proof', $order), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
            ]);
        $this->assertContains($proofResponse->getStatusCode(), [403, 404]);

        $signedDownloadUrl = URL::temporarySignedRoute('shop.orders.download', now()->addMinutes(10), ['order' => $order]);
        $downloadResponse = $this->actingAs($this->otherCustomer)->get($signedDownloadUrl);
        $this->assertContains($downloadResponse->getStatusCode(), [403, 404]);
    }

    public function test_upload_proof_rejects_exe_and_files_larger_than_2mb(): void
    {
        Storage::fake('local');

        $order = Order::create([
            'invoice_number' => 'INV-20261105-EFGH',
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'amount' => $this->product->price,
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_account_id' => $this->bankAccount->id,
            'status' => OrderStatus::Pending,
        ]);

        // 1. Menolak file .exe
        $exeFile = UploadedFile::fake()->create('malware.exe', 200, 'application/x-msdownload');

        $this->actingAs($this->customer)
            ->from(route('shop.orders.pay', $order))
            ->post(route('shop.orders.proof', $order), [
                'payment_proof' => $exeFile,
            ])
            ->assertSessionHasErrors('payment_proof');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertNull($order->fresh()->payment_proof);

        // 2. Menolak file > 2MB (misal 2500 KB)
        $largeFile = UploadedFile::fake()->image('bukti-besar.png')->size(2500);

        $this->actingAs($this->customer)
            ->from(route('shop.orders.pay', $order))
            ->post(route('shop.orders.proof', $order), [
                'payment_proof' => $largeFile,
            ])
            ->assertSessionHasErrors('payment_proof');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertNull($order->fresh()->payment_proof);

        // 3. Menerima file valid (<= 2MB, jpg/png/pdf) dan mengubah status ke waiting_verification
        $validFile = UploadedFile::fake()->image('bukti-valid.jpg')->size(500);

        $this->actingAs($this->customer)
            ->post(route('shop.orders.proof', $order), [
                'payment_proof' => $validFile,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('shop.orders.show', $order));

        $order->refresh();
        $this->assertSame(OrderStatus::WaitingVerification, $order->status);
        $this->assertNotNull($order->payment_proof);
        Storage::disk('local')->assertExists($order->payment_proof);
    }

    public function test_download_is_rejected_when_not_paid_and_succeeds_when_paid(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put($this->product->file_path, 'Isi file produk digital rahasia');

        $order = Order::create([
            'invoice_number' => 'INV-20261105-IJKL',
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'amount' => $this->product->price,
            'payment_method' => PaymentMethod::Qris,
            'payment_account_id' => $this->qrisAccount->id,
            'status' => OrderStatus::WaitingVerification,
        ]);

        $signedUrl = URL::temporarySignedRoute('shop.orders.download', now()->addMinutes(10), ['order' => $order]);

        // 1. Ditolak saat status belum paid (waiting_verification / pending / rejected)
        $this->actingAs($this->customer)
            ->get($signedUrl)
            ->assertForbidden();

        // 2. Berhasil diunduh saat status paid
        $order->update(['status' => OrderStatus::Paid]);

        $this->actingAs($this->customer)
            ->get($signedUrl)
            ->assertOk()
            ->assertDownload('mastering-tiktok.pdf');
    }
}

