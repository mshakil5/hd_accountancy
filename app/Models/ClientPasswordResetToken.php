<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientPasswordResetToken extends Model
{
    protected $table = 'client_password_reset_tokens';

    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    public $timestamps = false;
}