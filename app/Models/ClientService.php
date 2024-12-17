<?php

namespace App\Models;

use App\Models\Client;
use App\Models\Service;
use App\Models\ClientSubService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientService extends Model
{
    use HasFactory;
    protected $table = 'client_service';

    protected $guarded = [];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function clientSubServices()
    {
        return $this->hasMany(ClientSubService::class);
    }

     public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
