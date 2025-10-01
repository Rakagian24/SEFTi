<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'nama_barang',
        'qty',
        'satuan',
        'harga',
        'tipe',
    ];

    protected $casts = [
        'harga' => 'decimal:5',
        'tipe' => 'string',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
