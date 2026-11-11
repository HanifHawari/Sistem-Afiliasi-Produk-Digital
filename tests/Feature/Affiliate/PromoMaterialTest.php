<?php

namespace Tests\Feature\Affiliate;

use App\Enums\PromoMaterialType;
use App\Models\Product;
use App\Models\PromoMaterial;
use App\Models\ReferralLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PromoMaterialTest extends TestCase
{
    use RefreshDatabase;

    private User $affiliate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->affiliate = User::factory()->affiliate()->create();
    }

    private function createProduct(array $attributes = []): Product
    {
        static $counter = 1;
        $counter++;

        return Product::create(array_merge([
            'name' => "Produk Digital {$counter}",
            'slug' => "produk-digital-{$counter}",
            'category' => 'E-Book',
            'price' => 100000,
            'commission_rate' => 20,
            'is_active' => true,
        ], $attributes));
    }

    public function test_guest_cannot_access_promo_materials_page(): void
    {
        $this->get(route('affiliate.promo-materials.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_affiliate_roles_are_forbidden(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($admin)->get(route('affiliate.promo-materials.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('affiliate.promo-materials.index'))->assertForbidden();
    }

    public function test_affiliate_can_access_promo_materials_page(): void
    {
        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index'))
            ->assertOk()
            ->assertSee('Materi Promosi')
            ->assertSee('Temukan dan gunakan materi promosi produk');
    }

    public function test_promo_materials_from_inactive_products_are_not_displayed(): void
    {
        $activeProduct = $this->createProduct(['name' => 'Produk Aktif', 'is_active' => true]);
        $inactiveProduct = $this->createProduct(['name' => 'Produk Nonaktif', 'is_active' => false]);

        $activeProduct->promoMaterials()->create([
            'title' => 'Materi Produk Aktif',
            'type' => PromoMaterialType::Text,
            'caption' => 'Promo menarik produk aktif',
        ]);

        $inactiveProduct->promoMaterials()->create([
            'title' => 'Materi Produk Nonaktif',
            'type' => PromoMaterialType::Text,
            'caption' => 'Promo produk tidak aktif',
        ]);

        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index'))
            ->assertOk()
            ->assertSee('Materi Produk Aktif')
            ->assertDontSee('Materi Produk Nonaktif');
    }

    public function test_filter_by_product_works(): void
    {
        $productA = $this->createProduct(['name' => 'Produk Alpha']);
        $productB = $this->createProduct(['name' => 'Produk Beta']);

        $productA->promoMaterials()->create([
            'title' => 'Materi Khusus Alpha',
            'type' => PromoMaterialType::Text,
            'caption' => 'Caption Alpha',
        ]);

        $productB->promoMaterials()->create([
            'title' => 'Materi Khusus Beta',
            'type' => PromoMaterialType::Text,
            'caption' => 'Caption Beta',
        ]);

        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index', ['product_id' => $productA->id]))
            ->assertOk()
            ->assertSee('Materi Khusus Alpha')
            ->assertDontSee('Materi Khusus Beta');
    }

    public function test_filter_by_material_type_works(): void
    {
        $product = $this->createProduct();

        $product->promoMaterials()->create([
            'title' => 'Materi Banner Gambar',
            'type' => PromoMaterialType::Image,
            'file_path' => 'promo-materials/banner.png',
        ]);

        $product->promoMaterials()->create([
            'title' => 'Materi Video Reels',
            'type' => PromoMaterialType::Video,
            'file_path' => 'promo-materials/reels.mp4',
        ]);

        $product->promoMaterials()->create([
            'title' => 'Materi Copywriting WA',
            'type' => PromoMaterialType::Text,
            'caption' => 'Halo kawan, ini copywriting promo...',
        ]);

        // Filter type=image
        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index', ['type' => 'image']))
            ->assertOk()
            ->assertSee('Materi Banner Gambar')
            ->assertDontSee('Materi Video Reels')
            ->assertDontSee('Materi Copywriting WA');

        // Filter type=video
        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index', ['type' => 'video']))
            ->assertOk()
            ->assertDontSee('Materi Banner Gambar')
            ->assertSee('Materi Video Reels')
            ->assertDontSee('Materi Copywriting WA');

        // Filter type=text
        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index', ['type' => 'text']))
            ->assertOk()
            ->assertDontSee('Materi Banner Gambar')
            ->assertDontSee('Materi Video Reels')
            ->assertSee('Materi Copywriting WA');

        // Filter tipe tidak valid tidak menyebabkan error dan mengembalikan semua tipe
        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index', ['type' => 'invalid_type']))
            ->assertOk()
            ->assertSee('Materi Banner Gambar')
            ->assertSee('Materi Video Reels')
            ->assertSee('Materi Copywriting WA');
    }

    public function test_search_by_title_caption_and_product_name_works(): void
    {
        $productA = $this->createProduct(['name' => 'Mastering Laravel 11']);
        $productB = $this->createProduct(['name' => 'Vue JS Fundamentals']);

        $productA->promoMaterials()->create([
            'title' => 'Flyer Super Diskon',
            'type' => PromoMaterialType::Text,
            'caption' => 'Dapatkan diskon terbatas dengan voucher hemat.',
        ]);

        $productB->promoMaterials()->create([
            'title' => 'Story Instagram Template',
            'type' => PromoMaterialType::Text,
            'caption' => 'Belajar Vue dari nol hingga mahir.',
        ]);

        // Cari berdasarkan judul materi
        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index', ['q' => 'Flyer Super']))
            ->assertOk()
            ->assertSee('Flyer Super Diskon')
            ->assertDontSee('Story Instagram Template');

        // Cari berdasarkan caption
        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index', ['q' => 'voucher hemat']))
            ->assertOk()
            ->assertSee('Flyer Super Diskon')
            ->assertDontSee('Story Instagram Template');

        // Cari berdasarkan nama produk
        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index', ['q' => 'Vue JS Fundamentals']))
            ->assertOk()
            ->assertSee('Story Instagram Template')
            ->assertDontSee('Flyer Super Diskon');
    }

    public function test_affiliate_can_download_valid_file(): void
    {
        Storage::fake('public');

        $product = $this->createProduct(['name' => 'Ebook Laravel', 'slug' => 'ebook-laravel']);
        Storage::disk('public')->put('promo-materials/banner.png', 'fake image content');

        $material = $product->promoMaterials()->create([
            'title' => 'Banner Promo',
            'type' => PromoMaterialType::Image,
            'file_path' => 'promo-materials/banner.png',
        ]);

        $response = $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.download', $material));

        $response->assertOk();
        $response->assertDownload('ebook-laravel-banner-promo.png');
    }

    public function test_download_is_rejected_when_product_inactive_or_file_missing(): void
    {
        Storage::fake('public');

        $activeProduct = $this->createProduct(['is_active' => true]);
        $inactiveProduct = $this->createProduct(['is_active' => false]);

        // 1. File tidak ada secara fisik di storage
        $missingFileMaterial = $activeProduct->promoMaterials()->create([
            'title' => 'File Hilang',
            'type' => PromoMaterialType::Image,
            'file_path' => 'promo-materials/non_existent.png',
        ]);

        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.download', $missingFileMaterial))
            ->assertNotFound();

        // 2. Produk tidak aktif
        Storage::disk('public')->put('promo-materials/banner_inactive.png', 'content');
        $inactiveProductMaterial = $inactiveProduct->promoMaterials()->create([
            'title' => 'Materi Nonaktif',
            'type' => PromoMaterialType::Image,
            'file_path' => 'promo-materials/banner_inactive.png',
        ]);

        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.download', $inactiveProductMaterial))
            ->assertNotFound();

        // 3. Materi tipe text tanpa file_path
        $textMaterial = $activeProduct->promoMaterials()->create([
            'title' => 'Teks Tanpa File',
            'type' => PromoMaterialType::Text,
            'caption' => 'Ini hanya caption',
        ]);

        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.download', $textMaterial))
            ->assertNotFound();
    }

    public function test_referral_link_displayed_is_always_owned_by_logged_in_affiliate(): void
    {
        $otherAffiliate = User::factory()->affiliate()->create();
        $product = $this->createProduct();

        // Link milik affiliate login
        $myLink = ReferralLink::create([
            'affiliate_id' => $this->affiliate->id,
            'product_id' => $product->id,
            'code' => 'MYCODE123',
        ]);

        // Link milik afiliator lain untuk produk yang sama
        $otherLink = ReferralLink::create([
            'affiliate_id' => $otherAffiliate->id,
            'product_id' => $product->id,
            'code' => 'OTHERCODE999',
        ]);

        $product->promoMaterials()->create([
            'title' => 'Caption Materi',
            'type' => PromoMaterialType::Text,
            'caption' => 'Beli sekarang juga!',
        ]);

        $response = $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index'));

        $response->assertOk();
        $response->assertSee('MYCODE123');
        $response->assertSee($myLink->url());
        $response->assertDontSee('OTHERCODE999');
        $response->assertDontSee($otherLink->url());
    }

    public function test_combined_filters_work_together(): void
    {
        $productA = $this->createProduct(['name' => 'Produk Alpha']);
        $productB = $this->createProduct(['name' => 'Produk Beta']);

        // Material 1: Cocok semua (Product A, Image, Title ada kata Kunci)
        $productA->promoMaterials()->create([
            'title' => 'Banner Promo Kunci',
            'type' => PromoMaterialType::Image,
            'file_path' => 'promo-materials/a.png',
        ]);

        // Material 2: Product A, Video (type beda)
        $productA->promoMaterials()->create([
            'title' => 'Video Promo Kunci',
            'type' => PromoMaterialType::Video,
            'file_path' => 'promo-materials/b.mp4',
        ]);

        // Material 3: Product B, Image (product beda)
        $productB->promoMaterials()->create([
            'title' => 'Banner Promo Kunci B',
            'type' => PromoMaterialType::Image,
            'file_path' => 'promo-materials/c.png',
        ]);

        $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index', [
                'product_id' => $productA->id,
                'type' => 'image',
                'q' => 'Kunci',
            ]))
            ->assertOk()
            ->assertSee('Banner Promo Kunci')
            ->assertDontSee('Video Promo Kunci')
            ->assertDontSee('Banner Promo Kunci B');
    }

    public function test_pagination_paginates_12_items_per_page_and_preserves_query_string(): void
    {
        $product = $this->createProduct();

        for ($i = 1; $i <= 13; $i++) {
            $product->promoMaterials()->create([
                'title' => "Materi Batch Ke-{$i}",
                'type' => PromoMaterialType::Text,
                'caption' => "Caption {$i}",
            ]);
        }

        $response = $this->actingAs($this->affiliate)
            ->get(route('affiliate.promo-materials.index', ['type' => 'text']));

        $response->assertOk();
        // Pagination link should keep query params
        $response->assertSee('page=2');
        $response->assertSee('type=text');
    }
}
