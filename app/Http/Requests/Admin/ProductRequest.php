<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses sudah dibatasi middleware role:admin
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:1000', 'max:100000000'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            // Gambar sampul dipotong ke 16:10 & diperkecil otomatis (App\Services\Media\ProductThumbnail).
            // Batas sisi 4096 px menjaga pemakaian memori server saat gambar diproses.
            'thumbnail' => [
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120',
                'dimensions:min_width=640,min_height=400,max_width=4096,max_height=4096',
            ],
            'file' => ['nullable', 'file', 'max:51200'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'thumbnail.image' => 'Gambar sampul harus berupa gambar JPG, PNG, atau WebP.',
            'thumbnail.mimes' => 'Gambar sampul harus berupa gambar JPG, PNG, atau WebP.',
            'thumbnail.max' => 'Gambar sampul maksimal berukuran 5 MB.',
            'thumbnail.dimensions' => 'Gambar sampul minimal 640×400 px dan maksimal 4096 px per sisi.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama produk',
            'category' => 'kategori',
            'description' => 'deskripsi',
            'price' => 'harga jual',
            'commission_rate' => 'persentase komisi',
            'thumbnail' => 'gambar sampul',
            'file' => 'file produk',
        ];
    }
}
