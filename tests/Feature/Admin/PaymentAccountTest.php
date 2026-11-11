<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentAccountType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        Storage::fake('public');
    }

    private function bank(array $attributes = []): PaymentAccount
    {
        return PaymentAccount::create($attributes + [
            'type' => PaymentAccountType::Bank, 'bank_name' => 'BCA', 'account_number' => '123456789', 'account_holder' => 'PT Uji',
        ]);
    }

    public function test_page_shows_accounts_and_warns_when_none_active(): void
    {
        $this->actingAs($this->admin)->get(route('admin.payment-accounts.index'))
            ->assertOk()
            ->assertSee('Belum ada metode pembayaran yang aktif');

        $this->bank();

        $this->actingAs($this->admin)->get(route('admin.payment-accounts.index'))
            ->assertOk()
            ->assertSee('123456789')
            ->assertDontSee('Belum ada metode pembayaran yang aktif');
    }

    public function test_admin_can_add_bank_account(): void
    {
        $this->actingAs($this->admin)->post(route('admin.payment-accounts.store'), [
            'type' => 'bank', 'bank_name' => 'mandiri', 'account_number' => '137-000 123', 'account_holder' => 'PT Afiliasi', 'is_active' => '1',
        ])->assertRedirect(route('admin.payment-accounts.index'));

        $this->assertDatabaseHas('payment_accounts', [
            'type' => 'bank', 'bank_name' => 'MANDIRI', 'account_number' => '137000123', 'qris_image' => null,
        ]);
    }

    public function test_bank_account_requires_bank_and_number(): void
    {
        $this->actingAs($this->admin)->post(route('admin.payment-accounts.store'), [
            'type' => 'bank', 'account_holder' => 'PT Afiliasi', 'account_number' => 'abc',
        ])->assertSessionHasErrors(['bank_name', 'account_number']);
    }

    public function test_admin_can_add_qris_with_image(): void
    {
        $this->actingAs($this->admin)->post(route('admin.payment-accounts.store'), [
            'type' => 'qris', 'account_holder' => 'Toko Afiliasi', 'qris_image' => UploadedFile::fake()->image('qris.png'),
        ])->assertSessionHasNoErrors();

        $qris = PaymentAccount::firstWhere('type', 'qris');
        Storage::disk('public')->assertExists($qris->qris_image);
        $this->assertNull($qris->bank_name);
    }

    public function test_new_qris_requires_image_but_edit_does_not(): void
    {
        $this->actingAs($this->admin)->post(route('admin.payment-accounts.store'), [
            'type' => 'qris', 'account_holder' => 'Toko',
        ])->assertSessionHasErrors('qris_image');

        $qris = PaymentAccount::create(['type' => PaymentAccountType::Qris, 'account_holder' => 'Toko', 'qris_image' => 'qris/lama.png']);

        $this->actingAs($this->admin)->put(route('admin.payment-accounts.update', $qris), [
            'type' => 'qris', 'account_holder' => 'Toko Baru', 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Toko Baru', $qris->refresh()->account_holder);
        $this->assertSame('qris/lama.png', $qris->qris_image);
    }

    public function test_toggle_active(): void
    {
        $account = $this->bank();

        $this->actingAs($this->admin)->patch(route('admin.payment-accounts.toggle', $account));
        $this->assertFalse($account->refresh()->is_active);
    }

    public function test_used_account_cannot_be_deleted_but_unused_can(): void
    {
        $used = $this->bank();
        $product = Product::create(['name' => 'P', 'slug' => 'p', 'price' => 10000, 'commission_rate' => 10]);
        Order::create([
            'invoice_number' => 'INV-PA', 'customer_id' => User::factory()->customer()->create()->id, 'product_id' => $product->id,
            'amount' => 10000, 'payment_method' => PaymentMethod::BankTransfer, 'payment_account_id' => $used->id,
        ]);

        $this->actingAs($this->admin)->delete(route('admin.payment-accounts.destroy', $used))->assertSessionHas('error');
        $this->assertModelExists($used);

        $unused = $this->bank(['account_number' => '999999']);
        $this->actingAs($this->admin)->delete(route('admin.payment-accounts.destroy', $unused))->assertSessionHas('status');
        $this->assertModelMissing($unused);
    }

    public function test_non_admin_cannot_manage_accounts(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->post(route('admin.payment-accounts.store'), ['type' => 'bank'])
            ->assertForbidden();
    }
}
