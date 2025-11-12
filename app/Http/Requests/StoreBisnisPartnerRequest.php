<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBisnisPartnerRequest extends FormRequest
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
            'alamat' => 'required|string',
            'email' => 'nullable|email',
            'no_telepon' => 'nullable|string|max:50',
            'bank_id' => 'nullable|exists:banks,id',
            'nama_rekening' => 'nullable|string|max:255',
            'no_rekening_va' => 'nullable|string|max:255',
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
        ];
    }
}
