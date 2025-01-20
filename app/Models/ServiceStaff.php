<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceStaff extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'assigned_services' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

}
