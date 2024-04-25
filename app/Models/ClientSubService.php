<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientSubService extends Model
{
    use HasFactory;

    public function clientService()
    {
        return $this->belongsTo(ClientService::class);
    }
}
