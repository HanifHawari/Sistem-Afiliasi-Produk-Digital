<?php

namespace App\Http\Controllers\Affiliate;

use App\Enums\PromoMaterialType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PromoMaterial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PromoMaterialController extends Controller
{
    /**
     * Tampilkan daftar materi promosi untuk produk digital aktif.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Sanitasi Parameter Filter
        $search = mb_substr(trim(is_string($q = $request->query('q')) ? $q : ''), 0, 100);
        $productId = $request->filled('product_id') ? (int) $request->query('product_id') : null;

        $typeParam = is_string($t = $request->query('type')) ? $t : null;
        $selectedType = $typeParam !== null ? PromoMaterialType::tryFrom($typeParam)?->value : null;

        // 2. Query Materi Promosi (Hanya untuk produk aktif)
        $materials = PromoMaterial::query()
            ->whereHas('product', fn (Builder $query) => $query->active())
            ->with('product')
            ->when(! empty($productId), fn (Builder $query) => $query->where('product_id', $productId))
            ->when(! empty($selectedType), fn (Builder $query) => $query->where('type', $selectedType))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('caption', 'like', "%{$search}%")
                        ->orWhereHas('product', fn (Builder $pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        // 3. Daftar Produk Aktif untuk Pilihan Filter
        $products = Product::active()
            ->orderBy('name')
            ->get(['id', 'name']);

        // 4. Referral Link milik Afiliator Login (keyBy product_id)
        $userLinks = $user->referralLinks()
            ->get()
            ->keyBy('product_id');

        return view('affiliate.promo-materials.index', [
            'materials' => $materials,
            'products' => $products,
            'search' => $search,
            'selectedProduct' => $productId,
            'selectedType' => $selectedType,
            'userLinks' => $userLinks,
        ]);
    }

    /**
     * Unduh file materi promosi untuk produk aktif.
     */
    public function download(Request $request, PromoMaterial $promoMaterial): StreamedResponse
    {
        // 1. Pastikan produk terhubung dan aktif
        $promoMaterial->loadMissing('product');
        if (! $promoMaterial->product || ! $promoMaterial->product->is_active) {
            abort(404, 'Materi promosi atau produk tidak ditemukan.');
        }

        // 2. Pastikan file_path tidak kosong
        if (empty($promoMaterial->file_path)) {
            abort(404, 'File materi promosi tidak tersedia.');
        }

        // 3. Pastikan file ada di disk public
        if (! Storage::disk('public')->exists($promoMaterial->file_path)) {
            abort(404, 'File materi promosi tidak ditemukan pada server.');
        }

        // 4. Buat nama file yang aman dan rapi
        $extension = pathinfo($promoMaterial->file_path, PATHINFO_EXTENSION);
        $productSlug = Str::slug($promoMaterial->product->slug);
        $titleSlug = Str::slug($promoMaterial->title);

        $baseName = trim("{$productSlug}-{$titleSlug}", '-');
        if ($baseName === '') {
            $baseName = 'promo-material';
        }

        $safeFilename = $extension !== '' ? "{$baseName}.{$extension}" : $baseName;

        return Storage::disk('public')->download($promoMaterial->file_path, $safeFilename);
    }
}

