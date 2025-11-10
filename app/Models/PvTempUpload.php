<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PvTempUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'type',
        'path_tmp',
        'original_name',
        'size',
        'mime',
    ];
}
