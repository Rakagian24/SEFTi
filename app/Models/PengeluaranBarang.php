<?php

namespace App\Models;

use App\Scopes\DepartmentScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengeluaranBarang extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran_barang';

    protected $fillable = [
        'no_pengeluaran',
        'tanggal',
        'department_id',
        'jenis_pengeluaran',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function items()
    {
        return $this->hasMany(PengeluaranBarangItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
