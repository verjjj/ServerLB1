<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'student_name',
        'group_name',
        'subgroup',
        'date',
        'time',
        'type',
        'number',
        'subgroups',
        'visit',
        'has_credit'
    ];

    protected $casts = [
        'date' => 'date',
        'visit' => 'boolean',
        'has_credit' => 'boolean',
        'subgroup' => 'integer',
        'subgroups' => 'integer',
        'number' => 'integer'
    ];
} 