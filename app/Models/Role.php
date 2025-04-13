<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'created_at',
        'updated_at'
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withTimestamps()
            ->withPivot('deleted_at')
            ->wherePivotNull('deleted_at');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role')
            ->withTimestamps()
            ->withPivot('deleted_at')
            ->wherePivotNull('deleted_at');
    }

    public static function create(array $data): Role
    {
        if (self::where('name', $data['name'])->exists()) {
            throw new \InvalidArgumentException("Role with name '{$data['name']}' already exists.");
        }

        return parent::create($data);
    }
}
