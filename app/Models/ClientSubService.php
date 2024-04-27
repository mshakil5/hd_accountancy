<?php

namespace App\Models;

use App\Models\SubService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientSubService extends Model
{
    use HasFactory;

    public function clientService()
    {
        return $this->belongsTo(ClientService::class);
    }

    public function subService()
    {
        return $this->belongsTo(SubService::class);
    }
}
