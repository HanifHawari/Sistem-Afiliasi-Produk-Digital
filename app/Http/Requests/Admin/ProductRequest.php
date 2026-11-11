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
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'file' => ['nullable', 'file', 'max:51200'],
            'is_active' => ['boolean'],
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
            'thumbnail' => 'thumbnail',
            'file' => 'file produk',
        ];
    }
}
