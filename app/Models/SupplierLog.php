<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
