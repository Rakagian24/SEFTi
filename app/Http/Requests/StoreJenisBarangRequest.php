<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJenisBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_jenis_barang' => 'required|string|max:150|unique:jenis_barangs,nama_jenis_barang',
            'singkatan' => 'nullable|string|max:50|unique:jenis_barangs,singkatan',
            'status' => 'required|in:active,inactive',
        ];
    }
}
