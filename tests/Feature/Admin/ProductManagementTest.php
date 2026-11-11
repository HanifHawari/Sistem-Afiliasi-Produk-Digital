<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    private function product(array $attributes = []): Product
    {
        return Product::create($attributes + [
            'name' => 'Template Uji', 'slug' => 'template-uji', 'price' => 99000, 'commission_rate' => 50,
        ]);
    }

    public function test_products_page_renders(): void
    {
        $product = $this->product();

        $this->actingAs($this->admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('Rp'."\u{00A0}".'49.500 / unit', false);
    }

    public function test_admin_can_create_product_with_files(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Kelas Desain Canva',
            'category' => 'E-Course',
            'price' => 150000,
            'commission_rate' => 40,
            'is_active' => '1',
            'thumbnail' => UploadedFile::fake()->image('thumb.jpg'),
            'file' => UploadedFile::fake()->create('materi.zip', 100),
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::firstWhere('slug', 'kelas-desain-canva');
        $this->assertNotNull($product);
        $this->assertSame(60000, $product->commissionAmount());
        Storage::disk('public')->assertExists($product->thumbnail);
        Storage::disk('local')->assertExists($product->file_path);
    }

    public function test_duplicate_names_get_unique_slugs(): void
    {
        $this->product(['name' => 'Ebook', 'slug' => 'ebook']);

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Ebook', 'price' => 50000, 'commission_rate' => 30,
        ]);

        $this->assertDatabaseHas('products', ['slug' => 'ebook-2']);
    }

    public function test_validation_errors_reopen_form(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.products.index'))
            ->post(route('admin.products.store'), ['_form' => 'product', 'name' => '', 'price' => 10, 'commission_rate' => 150])
            ->assertSessionHasErrors(['name', 'price', 'commission_rate']);
    }

    public function test_admin_can_update_price_and_commission(): void
    {
        $product = $this->product();

        $this->actingAs($this->admin)->put(route('admin.products.update', $product->id), [
            'name' => 'Template Uji', 'price' => 120000, 'commission_rate' => 35, 'is_active' => '1',
        ])->assertSessionHas('status');

        $product->refresh();
        $this->assertSame(120000, $product->price);
        $this->assertEquals(35, $product->commission_rate);
    }

    public function test_toggle_deactivates_and_reactivates(): void
    {
        $product = $this->product();

        $this->actingAs($this->admin)->patch(route('admin.products.toggle', $product->id));
        $this->assertFalse($product->refresh()->is_active);

        $this->actingAs($this->admin)->patch(route('admin.products.toggle', $product->id));
        $this->assertTrue($product->refresh()->is_active);
    }

    public function test_product_with_orders_cannot_be_deleted(): void
    {
        $product = $this->product();
        Order::create([
            'invoice_number' => 'INV-X', 'customer_id' => User::factory()->customer()->create()->id,
            'product_id' => $product->id, 'amount' => 99000, 'payment_method' => PaymentMethod::Qris,
        ]);

        $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product->id))->assertSessionHas('error');
        $this->assertModelExists($product);
    }

    public function test_promo_materials_can_be_added_and_removed(): void
    {
        Storage::fake('public');
        $product = $this->product();

        $this->actingAs($this->admin)
            ->get(route('admin.products.promo-materials.index', $product->id))
            ->assertOk();

        $this->actingAs($this->admin)->post(route('admin.products.promo-materials.store', $product->id), [
            'title' => 'Banner Story', 'type' => 'image', 'file' => UploadedFile::fake()->image('banner.png'),
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->post(route('admin.products.promo-materials.store', $product->id), [
            'title' => 'Caption', 'type' => 'text',
        ])->assertSessionHasErrors('caption');

        $material = $product->promoMaterials()->first();
        Storage::disk('public')->assertExists($material->file_path);

        $this->actingAs($this->admin)->delete(route('admin.promo-materials.destroy', $material));
        $this->assertModelMissing($material);
    }

    public function test_dashboard_renders_with_data(): void
    {
        // Seeder membuat & membersihkan file contoh: jangan sentuh folder storage asli
        Storage::fake('local');
        Storage::fake('public');
        $this->seed();

        $this->actingAs(User::firstWhere('email', 'hanif@afiliasi.test'))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Selamat Datang, Hanif')
            ->assertSee('Pembayaran Menunggu Verifikasi')
            ->assertSee('Gibran')
            ->assertSee('Bagus');
    }
}
