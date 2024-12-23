<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientSubService extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_service_id',
        'client_id',
        'sub_service_id',
        'manager_id',
        'staff_id',
        'deadline',
        'note',
        'status',
        'sequence_id',
        'sequence_status',
        'manager_notification',
        'staff_notification',
        'created_by',
        'updated_by',
    ];


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

    public function workTimes()
    {
        return $this->hasMany(WorkTime::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
