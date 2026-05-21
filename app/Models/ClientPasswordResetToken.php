<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPasswordResetToken extends Model
{
    public $timestamps      = false;
    protected $table        = 'client_password_reset_tokens';
    protected $primaryKey   = 'email';
    public $keyType         = 'string';
    public $incrementing    = false;

    protected $fillable = ['email', 'token', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}