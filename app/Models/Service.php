<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'tag',
        'status'
    ];

    public function clients()
    {
          return $this->belongsToMany(Client::class, 'client_service', 'service_id', 'client_id');
    }

}
