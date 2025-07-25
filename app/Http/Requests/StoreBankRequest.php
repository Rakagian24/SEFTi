<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankRequest extends FormRequest
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
            // 'kode_bank' => 'nullable|string|max:10|unique:banks,kode_bank',
            'nama_bank' => 'required|string|max:100|unique:banks,nama_bank',
            'singkatan' => 'nullable|string|max:20|unique:banks,singkatan',
            'status' => 'required|in:active,non-active',
            'currency' => 'required|in:IDR,USD',
        ];
    }

    public function messages()
    {
        return [
            // 'kode_bank.required' => 'Kode Bank wajib diisi.',
            // 'kode_bank.unique' => 'Kode Bank sudah terdaftar, silakan gunakan kode lain.',
            // 'kode_bank.max' => 'Kode Bank maksimal :max karakter.',
            'nama_bank.required' => 'Nama Bank wajib diisi.',
            'nama_bank.unique' => 'Nama Bank sudah terdaftar.',
            'nama_bank.max' => 'Nama Bank maksimal :max karakter.',
            'singkatan.required' => 'Singkatan wajib diisi.',
            'singkatan.unique' => 'Singkatan sudah terdaftar.',
            'singkatan.max' => 'Singkatan maksimal :max karakter.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status harus Active atau Inactive.',
            'currency.required' => 'Mata uang wajib dipilih.',
            'currency.in' => 'Mata uang harus IDR atau USD.',
        ];
    }
}
