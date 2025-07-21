<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArPartner extends Model
{
    protected $fillable = [
        'nama_ap',
        'jenis_ap',
        'alamat',
        'email',
        'no_telepon',
        'contact_person',
        'department_id',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
