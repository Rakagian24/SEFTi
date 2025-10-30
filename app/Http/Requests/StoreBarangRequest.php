<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_barang' => 'required|string|max:150|unique:barangs,nama_barang',
            'jenis_barang_id' => 'required|exists:jenis_barangs,id',
            'satuan' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
        ];
    }
}
