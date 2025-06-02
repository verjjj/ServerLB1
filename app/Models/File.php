<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'format',
        'size',
        'path',
        'data',
        'original_name',
        'mime_type'
    ];

    protected $casts = [
        'size' => 'integer',
        'data' => 'binary'
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'photo_id');
    }

    public function getAvatarPath()
    {
        $pathInfo = pathinfo($this->path);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_avatar.' . $pathInfo['extension'];
    }
}
