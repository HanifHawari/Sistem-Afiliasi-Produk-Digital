<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $selectedCategory = trim((string) $request->query('category', ''));

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
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            }))
            ->when($selectedCategory !== '', fn (Builder $query) => $query->where('category', $selectedCategory))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('shop.products.index', compact('products', 'categories', 'search', 'selectedCategory'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        return view('shop.products.show', compact('product'));
    }
}

