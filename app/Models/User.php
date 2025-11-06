<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasActiveStatus;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasActiveStatus;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'photo',
        'passcode',
        'role_id',
        'department_id',
        'status',
        'extra_permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'passcode', // sembunyikan passcode
    ];


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'string',
            'extra_permissions' => 'array',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }

    public function conversationsAsUserOne()
    {
        return $this->hasMany(Conversation::class, 'user_one_id');
    }

    public function conversationsAsUserTwo()
    {
        return $this->hasMany(Conversation::class, 'user_two_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function createdBankMasuks()
    {
        return $this->hasMany(BankMasuk::class, 'created_by');
    }

    public function updatedBankMasuks()
    {
        return $this->hasMany(BankMasuk::class, 'updated_by');
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission($permission)
    {
        $userPermissions = $this->getPermissions();

        // Admin has all permissions
        if (in_array('*', $userPermissions)) {
            return true;
        }

        return in_array($permission, $userPermissions);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission($permissions)
    {
        $userPermissions = $this->getPermissions();

        // Admin has all permissions
        if (in_array('*', $userPermissions)) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions($permissions)
    {
        $userPermissions = $this->getPermissions();

        // Admin has all permissions
        if (in_array('*', $userPermissions)) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roleNames)
    {
        if (!$this->role) {
            return false;
        }

        return in_array($this->role->name, $roleNames);
    }

    /**
     * Get user's permissions
     */
    public function getPermissions()
    {
        $rolePermissions = $this->role ? ($this->role->permissions ?? []) : [];
        $extra = $this->extra_permissions ?? [];
        if (!is_array($rolePermissions)) { $rolePermissions = []; }
        if (!is_array($extra)) { $extra = []; }
        // Merge and deduplicate, keep '*'
        $merged = array_values(array_unique(array_merge($rolePermissions, $extra)));
        return $merged;
    }

    /**
     * Check if user can access menu based on role permissions
     */
    public function canAccessMenu($menuPermission)
    {
        return $this->hasPermission($menuPermission);
    }
}
