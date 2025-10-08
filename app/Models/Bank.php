<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasActiveStatus;

class Bank extends Model
{
    use SoftDeletes;
    use HasActiveStatus;

    protected $fillable = [
        // 'kode_bank',
        'nama_bank',
        'singkatan',
        'status',
        'currency',
    ];

    // Relasi dengan BisnisPartner
    public function bisnisPartners()
    {
        return $this->hasMany(BisnisPartner::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'bank_supplier_accounts')
            ->withPivot('nama_rekening', 'no_rekening')
            ->withTimestamps();
    }
}
