<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => 'required|exists:departments,id',
            'no_rekening' => 'required|string|max:255|unique:bank_accounts,no_rekening,NULL,id,bank_id,' . $this->bank_id,
            'bank_id' => 'required|exists:banks,id',
            'status' => 'required|in:active,inactive',
        ];
    }

    public function messages()
    {
        return [
            'department_id.required' => 'Department wajib dipilih.',
            'department_id.exists' => 'Department tidak valid.',
            'no_rekening.required' => 'No Rekening wajib diisi.',
            'no_rekening.unique' => 'No Rekening sudah terdaftar pada bank ini.',
            'bank_id.required' => 'Bank wajib dipilih.',
            'bank_id.exists' => 'Bank tidak valid.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status harus Active atau Inactive.',
        ];
    }
}
