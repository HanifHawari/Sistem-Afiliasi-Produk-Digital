<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ReferralLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Tampilkan katalog produk digital aktif untuk afiliator.
     */
    public function index(Request $request): View
    {
        $search = mb_substr(trim(is_string($q = $request->query('q')) ? $q : ''), 0, 100);
        $selectedCategory = trim(is_string($cat = $request->query('category')) ? $cat : '');

        $categories = Product::active()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        if ($selectedCategory !== '' && ! $categories->contains($selectedCategory)) {
            $selectedCategory = '';
        }

        $products = Product::active()
            ->withCount('promoMaterials')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            }))
            ->when($selectedCategory !== '', fn (Builder $query) => $query->where('category', $selectedCategory))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $user = $request->user();
        $userLinks = $user->referralLinks()
            ->get()
            ->keyBy('product_id');

        return view('affiliate.products.index', compact(
            'products',
            'categories',
            'search',
            'selectedCategory',
            'userLinks',
        ));
    }

    /**
     * Generate referral link unik untuk produk bagi afiliator yang sedang login.
     */
    public function generateLink(Request $request, Product $product): RedirectResponse
    {
        // Validasi produk harus aktif
        if (! $product->is_active) {
            return back()->with('error', 'Produk ini sedang tidak aktif dan belum dapat dipromosikan.');
        }

        $user = $request->user();

        // Cek apakah afiliator sudah punya link untuk produk ini
        $existingLink = $user->referralLinks()->where('product_id', $product->id)->first();
        if ($existingLink) {
            return back()
                ->with('status', 'Link referral sudah tersedia')
                ->with('status_detail', "Link referral Anda: {$existingLink->url()}");
        }

        // Generate kode referral unik dalam transaksi database
        DB::transaction(function () use ($user, $product) {
            // Lock dan cek kembali untuk proteksi race condition / double-click
            $alreadyExists = ReferralLink::where('affiliate_id', $user->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if ($alreadyExists) {
                return;
            }

            // Prefix dari nama afiliator (alphanumeric, huruf besar, maks 8 karakter)
            $rawPrefix = preg_replace('/[^A-Za-z0-9]/', '', (string) $user->name);
            $prefix = Str::upper(substr($rawPrefix, 0, 8));
            if ($prefix === '') {
                $prefix = 'REF';
            }

            $code = null;
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $candidate = substr($prefix.Str::upper(Str::random(6)), 0, 32);
                if (! ReferralLink::where('code', $candidate)->exists()) {
                    $code = $candidate;
                    break;
                }
            }

            if (! $code) {
                $code = substr('REF'.Str::upper(Str::random(12)), 0, 32);
            }

            ReferralLink::create([
                'affiliate_id' => $user->id,
                'product_id' => $product->id,
                'code' => $code,
            ]);
        });

        return back()
            ->with('status', 'Link referral berhasil dibuat!')
            ->with('status_detail', "Link referral unik Anda untuk \"{$product->name}\" siap dibagikan.");
    }
}
