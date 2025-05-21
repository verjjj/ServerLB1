<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogRequest extends Model
{
    protected $table = 'logs_requests';

    protected $fillable = [
        'full_url',
        'http_method',
        'controller_path',
        'controller_method',
        'request_body',
        'request_headers',
        'user_id',
        'ip_address',
        'user_agent',
        'response_status',
        'response_body',
        'response_headers',
        'called_at',
    ];
}
