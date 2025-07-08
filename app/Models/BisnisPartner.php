<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BisnisPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_bp', 'jenis_bp', 'alamat', 'email', 'no_telepon',
        'nama_bank', 'nama_rekening', 'no_rekening_va', 'terms_of_payment', 'status'
    ];
}
