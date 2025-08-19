<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Scopes\DepartmentScope;
use App\Models\ArPartnerLog;

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

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function bankMasuks()
    {
        return $this->hasMany(BankMasuk::class);
    }

    public function logs()
    {
        return $this->hasMany(ArPartnerLog::class);
    }

    /**
     * Scope untuk search yang dioptimasi
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('nama_ap', 'like', '%' . $search . '%')
              ->orWhere('jenis_ap', 'like', '%' . $search . '%')
              ->orWhere('alamat', 'like', '%' . $search . '%')
              ->orWhere('no_telepon', 'like', '%' . $search . '%')
              ->orWhere('email', 'like', '%' . $search . '%');
        });
    }

    /**
     * Scope untuk filter berdasarkan jenis_ap
     */
    public function scopeByJenisAp($query, $jenisAp)
    {
        return $query->where('jenis_ap', $jenisAp);
    }

    /**
     * Scope untuk filter berdasarkan department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
}
