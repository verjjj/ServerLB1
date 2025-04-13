<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
class User extends Authenticatable
{
    use  HasFactory, Notifiable, SoftDeletes;
    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withTimestamps()
            ->withPivot('deleted_at')
            ->wherePivotNull('deleted_at');
    }

    public function permissions()
    {
        return $this->hasManyThrough(
            Permission::class,
            RolesPermissions::class,
            'role_id',
            'id',
            'id',
            'permission_id'
        )->whereNull('role_permission.deleted_at');
    }

    public function hasPermission(string $permissionCode): bool
    {
        // Проверка напрямую назначенных разрешений
        $directPermission = $this->permissions()
            ->where('code', $permissionCode)
            ->exists();

        // Проверка разрешений через роли
        $rolesPermissions = $this->roles()
            ->whereHas('permissions', fn($query) =>
            $query->where('code', $permissionCode)
            )
            ->exists();

        return $directPermission || $rolesPermissions;
    }

}
