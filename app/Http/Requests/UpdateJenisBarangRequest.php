<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJenisBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('jenis_barang') ?? $this->route('id');
        return [
            'nama_jenis_barang' => 'required|string|max:150|unique:jenis_barangs,nama_jenis_barang,' . $id,
            'singkatan' => 'nullable|string|max:50|unique:jenis_barangs,singkatan,' . $id,
            'status' => 'required|in:active,inactive',
        ];
    }
}
