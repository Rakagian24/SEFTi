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
    ];

}
