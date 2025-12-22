<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasActiveStatus;
use App\Models\Department;
use App\Models\Supplier;

class Barang extends Model
{
    use SoftDeletes;
    use HasActiveStatus;

    protected $fillable = [
        'nama_barang',
        'jenis_barang_id',
        'supplier_id',
        'satuan',
        'department_id',
        'status',
    ];

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'barang_department');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
