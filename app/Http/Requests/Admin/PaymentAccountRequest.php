<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentAccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentAccountRequest extends FormRequest
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
        $isBank = $this->input('type') === PaymentAccountType::Bank->value;
        // QRIS baru wajib upload gambar; saat edit boleh kosong (pakai gambar lama)
        $needsImage = ! $isBank && ! $this->route('paymentAccount')?->qris_image;

        return [
            'type' => ['required', Rule::enum(PaymentAccountType::class)],
            'bank_name' => [Rule::requiredIf($isBank), 'nullable', 'string', 'max:50'],
            'account_number' => [Rule::requiredIf($isBank), 'nullable', 'regex:/^[0-9\-\s]{5,30}$/'],
            'account_holder' => ['required', 'string', 'max:100'],
            'qris_image' => [Rule::requiredIf($needsImage), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'jenis',
            'bank_name' => 'nama bank',
            'account_number' => 'nomor rekening',
            'account_holder' => 'nama pemilik',
            'qris_image' => 'gambar QRIS',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_number.regex' => 'Nomor rekening hanya boleh berisi angka (5-30 digit).',
        ];
    }
}
