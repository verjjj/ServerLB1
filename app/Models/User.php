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
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, Authorizable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'birthday',
    ];

    protected $dates = ['deleted_at'];

    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public $timestamps = true;

    public function roles(): BelongsToMany
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
        $cacheKey = "user_{$this->id}_permission_{$permissionCode}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

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

        $result = $directPermission || $rolesPermissions;
        Cache::put($cacheKey, $result, now()->addMinutes(60));

        return $result;
    }

    public function twoFactorAuth()
    {
        return $this->hasOne(TwoFactorAuth::class);
    }

    public function logRequests()
    {
        return $this->hasMany(LogRequest::class, 'user_id');
    }
}
