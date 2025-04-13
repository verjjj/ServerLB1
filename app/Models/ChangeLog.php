<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeLog extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'before',
        'after',
        'action',
    ];
}
