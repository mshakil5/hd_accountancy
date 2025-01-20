<?php

namespace App\Models;

use App\Models\Client;
use App\Models\SubService;
use App\Models\ClientService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function clients()
    {
          return $this->belongsToMany(Client::class, 'client_service', 'service_id', 'client_id');
    }

    public function clientServices()
    {
        return $this->belongsToMany(ClientService::class);
    }

    public function subServices()
    {
        return $this->hasMany(SubService::class);
    }

}
