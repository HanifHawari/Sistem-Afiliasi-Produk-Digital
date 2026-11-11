<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PromoMaterialType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PromoMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Materi promosi per produk, diunduh afiliator di menu "Materi Promosi".
 */
class PromoMaterialController extends Controller
{
    public function index(Product $product): View
    {
        $materials = $product->promoMaterials()->latest()->get();

        return view('admin.products.promo-materials', compact('product', 'materials'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $isText = $request->input('type') === PromoMaterialType::Text->value;

        // File materi tersimpan di folder publik, jadi jenisnya selalu dibatasi ke gambar atau video.
        // Materi teks tidak menyimpan file sama sekali: file yang ikut terkirim diabaikan.
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(PromoMaterialType::class)],
            'file' => $isText ? ['exclude'] : [
                'required', 'file', 'max:51200',
                $request->input('type') === PromoMaterialType::Video->value ? 'mimes:mp4,mov,webm' : 'mimes:jpg,jpeg,png,webp,gif',
            ],
            'caption' => [Rule::requiredIf($isText), 'nullable', 'string', 'max:2000'],
        ], [], ['title' => 'judul', 'type' => 'jenis', 'file' => 'file', 'caption' => 'caption']);

        if (! $isText) {
            $data['file_path'] = $request->file('file')->store('promo-materials', 'public');
        }
        unset($data['file']);

        $product->promoMaterials()->create($data);

        return back()->with('status', 'Materi promosi ditambahkan');
    }

    public function destroy(PromoMaterial $promoMaterial): RedirectResponse
    {
        Storage::disk('public')->delete((string) $promoMaterial->file_path);
        $promoMaterial->delete();

        return back()->with('status', 'Materi promosi dihapus');
    }
}
