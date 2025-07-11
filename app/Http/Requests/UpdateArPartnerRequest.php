<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArPartnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_ap' => 'required|string|max:100',
            'jenis_ap' => 'required|string|max:100',
            'alamat' => 'required|string|max:255',
            'no_telepon' => 'required|string|max:20',
            'email' => 'required|email|max:100',
        ];
    }
}
