<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Gambar sampul produk: apa pun rasio aslinya, tersimpan 16:10 dengan lebar maksimal 1600 px.
 */
class ProductThumbnailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array{0: int, 1: int} lebar & tinggi gambar sampul yang tersimpan
     */
    private function storeWith(UploadedFile $thumbnail, string $name = 'Produk Uji'): array
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => $name, 'price' => 50000, 'commission_rate' => 30, 'is_active' => '1',
            'thumbnail' => $thumbnail,
        ])->assertSessionHasNoErrors();

        $path = Product::firstWhere('name', $name)->thumbnail;
        Storage::disk('public')->assertExists($path);
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));

        return [$width, $height];
    }

    public function test_portrait_image_is_cropped_to_16_by_10(): void
    {
        // Rasio 9:16 seperti foto HP: lebar dipertahankan, tinggi dipotong dari tengah
        $this->assertSame([1080, 675], $this->storeWith(UploadedFile::fake()->image('potret.jpg', 1080, 1920)));
    }

    public function test_wide_image_is_cropped_to_16_by_10(): void
    {
        $this->assertSame([800, 500], $this->storeWith(UploadedFile::fake()->image('lebar.png', 1500, 500)));
    }

    public function test_large_image_is_scaled_down_to_1600_wide(): void
    {
        $this->assertSame([1600, 1000], $this->storeWith(UploadedFile::fake()->image('besar.jpg', 3200, 2400)));
    }

    public function test_image_between_two_and_five_megabytes_is_accepted(): void
    {
        $this->assertSame([1280, 800], $this->storeWith(UploadedFile::fake()->image('tiga-mb.jpg', 1280, 800)->size(3000)));
    }

    public function test_stored_thumbnail_is_smaller_than_upload_limit(): void
    {
        $this->storeWith(UploadedFile::fake()->image('besar.jpg', 3200, 2400));

        $path = Product::firstWhere('name', 'Produk Uji')->thumbnail;
        $this->assertLessThan(1024 * 1024, Storage::disk('public')->size($path));
        $this->assertStringStartsWith('products/thumbnails/', $path);
    }

    public function test_invalid_thumbnails_are_rejected(): void
    {
        $post = fn (UploadedFile $file) => $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Ditolak', 'price' => 50000, 'commission_rate' => 30, 'thumbnail' => $file,
        ]);

        $post(UploadedFile::fake()->image('kecil.jpg', 300, 200))
            ->assertSessionHasErrors(['thumbnail' => 'Gambar sampul minimal 640×400 px dan maksimal 4096 px per sisi.']);
        $post(UploadedFile::fake()->image('berat.jpg', 1280, 800)->size(6000))
            ->assertSessionHasErrors(['thumbnail' => 'Gambar sampul maksimal berukuran 5 MB.']);
        $post(UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'))
            ->assertSessionHasErrors('thumbnail');

        $this->assertDatabaseMissing('products', ['name' => 'Ditolak']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_updating_thumbnail_replaces_old_file(): void
    {
        $this->storeWith(UploadedFile::fake()->image('lama.jpg', 1200, 800));
        $product = Product::firstWhere('name', 'Produk Uji');
        $old = $product->thumbnail;

        $this->actingAs($this->admin)->put(route('admin.products.update', $product->id), [
            'name' => 'Produk Uji', 'price' => 50000, 'commission_rate' => 30, 'is_active' => '1',
            'thumbnail' => UploadedFile::fake()->image('baru.jpg', 1080, 1920),
        ])->assertSessionHasNoErrors();

        $new = $product->fresh()->thumbnail;
        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    public function test_product_list_shows_thumbnail_and_marks_missing_one(): void
    {
        $this->storeWith(UploadedFile::fake()->image('sampul.jpg', 1200, 800), 'Punya Sampul');
        Product::create(['name' => 'Tanpa Sampul', 'slug' => 'tanpa-sampul', 'price' => 10000, 'commission_rate' => 10]);

        $this->actingAs($this->admin)->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('storage/'.Product::firstWhere('name', 'Punya Sampul')->thumbnail)
            ->assertSee('Belum ada gambar sampul')
            ->assertSee('Otomatis dipotong ke rasio 16:10');
    }
}
