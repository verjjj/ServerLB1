<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\TwoFactorAuthController;

class TwoFactorAuth extends Model
{
    protected $table = 'two_factor_auths';

    protected $fillable = [
        'user_id',
        'code',
        'code_expires_at',
        'client_identifier', 
        'is_enabled',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
