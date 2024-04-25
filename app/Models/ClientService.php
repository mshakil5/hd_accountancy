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

    protected $fillable = [
        'client_id',
        'assigned_service',
        'deadline',
        'status',
        'manager_id'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class);
    }

    public function clientSubServices()
    {
        return $this->hasMany(ClientSubService::class);
    }
}
