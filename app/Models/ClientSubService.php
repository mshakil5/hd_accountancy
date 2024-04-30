<?php

namespace App\Models;


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

    public function serviceMessage()
    {
        return $this->hasMany(ServiceMessage::class);
    }

    public function workTime()
    {
        return $this->hasOne(WorkTime::class);
    }
}
