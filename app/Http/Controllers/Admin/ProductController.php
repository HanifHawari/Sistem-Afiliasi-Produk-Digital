<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Models\PromoMaterial;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Produk & skema komisi (desain: design/stitch/hanif_admin-dashboard_produk-komisi)
 */
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->withCount(['orders as sold_count' => fn ($q) => $q->where('status', OrderStatus::Paid)])
            ->withSum(['orders as revenue' => fn ($q) => $q->where('status', OrderStatus::Paid)], 'amount')
            ->withCount('promoMaterials')
            ->when($search !== '', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(8)
            ->withQueryString();

        $pendingWithdrawals = Withdrawal::with('affiliate')
            ->where('status', WithdrawalStatus::Pending)
            ->oldest()
            ->limit(5)
            ->get();

        $stats = [
            'active_products' => Product::active()->count(),
            'new_products_month' => Product::where('created_at', '>=', now()->startOfMonth())->count(),
            'affiliates' => User::where('role', UserRole::Affiliate)->count(),
            'new_affiliates_week' => User::where('role', UserRole::Affiliate)->where('created_at', '>=', now()->subWeek())->count(),
            'commission_paid' => (int) Withdrawal::where('status', WithdrawalStatus::Paid)->sum('amount'),
            'withdrawal_pending_sum' => (int) Withdrawal::where('status', WithdrawalStatus::Pending)->sum('amount'),
            'withdrawal_pending_count' => Withdrawal::where('status', WithdrawalStatus::Pending)->count(),
            'promo_materials' => PromoMaterial::count(),
        ];

        $categories = Product::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        return view('admin.products.index', compact('products', 'pendingWithdrawals', 'stats', 'categories', 'search'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['thumbnail', 'file']);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }
        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('products/files', 'local');
        }

        $product = Product::create($data);

        return redirect()->route('admin.products.index')
            ->with('status', 'Produk berhasil diterbitkan')
            ->with('status_detail', "{$product->name} sekarang bisa dipromosikan afiliator.");
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except(['thumbnail', 'file']);
        $data['is_active'] = $request->boolean('is_active');

        if ($product->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }
        if ($request->hasFile('thumbnail')) {
            Storage::disk('public')->delete((string) $product->thumbnail);
            $data['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }
        if ($request->hasFile('file')) {
            Storage::disk('local')->delete((string) $product->file_path);
            $data['file_path'] = $request->file('file')->store('products/files', 'local');
        }

        $product->update($data);

        return back()
            ->with('status', 'Produk diperbarui')
            ->with('status_detail', 'Harga & komisi baru berlaku untuk transaksi berikutnya.');
    }

    public function toggle(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('status', $product->is_active
            ? "{$product->name} diaktifkan"
            : "{$product->name} dinonaktifkan");
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orders()->exists()) {
            return back()->with('error', 'Produk sudah punya transaksi. Nonaktifkan saja, jangan dihapus.');
        }

        Storage::disk('public')->delete(array_filter([
            $product->thumbnail,
            ...$product->promoMaterials()->pluck('file_path')->all(),
        ]));
        Storage::disk('local')->delete((string) $product->file_path);
        $product->delete();

        return back()->with('status', "{$product->name} dihapus");
    }

    /**
     * Unduh file produk (untuk pengecekan admin).
     */
    public function download(Product $product): StreamedResponse
    {
        abort_unless($product->file_path && Storage::disk('local')->exists($product->file_path), 404);

        $extension = pathinfo($product->file_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($product->file_path, $product->slug.'.'.$extension);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'produk';
        $slug = $base;
        $i = 2;

        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
