<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_bp' => 'required|string|max:255',
            'jenis_bp' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'email' => 'nullable|email',
            'no_telepon' => 'nullable|string|max:50',
            'nama_bank' => 'nullable|string|max:255',
            'nama_rekening' => 'nullable|string|max:255',
            'no_rekening_va' => 'nullable|string|max:255',
            'terms_of_payment' => 'nullable|string|max:255',
        ];
    }
}
