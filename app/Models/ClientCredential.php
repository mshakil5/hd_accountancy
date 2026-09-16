<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ClientCredential extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'status',
        'id_number',
        'created_by',
        'updated_by'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->ensureUniqueCreatedAt();
        });
    }

    protected function ensureUniqueCreatedAt(): void
    {
        $second = now()->format('Y-m-d H:i:s');
        while (static::where('created_at', 'like', $second . '%')->exists()) {
            $this->created_at = now()->addSecond();
            $second = $this->created_at->format('Y-m-d H:i:s');
        }
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function profile()
    {
        return $this->hasOne(Client::class, 'client_credential_id');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'client_credential_id');
    }
}