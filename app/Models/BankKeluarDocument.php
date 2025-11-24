<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankKeluarDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bank_keluar_id',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'path',
        'is_active',
        'uploaded_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'size' => 'integer',
    ];

    public function bankKeluar()
    {
        return $this->belongsTo(BankKeluar::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
