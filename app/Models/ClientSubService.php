<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ClientSubService extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'client_sub_service';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff(array_keys($this->getAttributes()), ['created_at', 'updated_at', 'deleted_at']))
            ->useLogName('client_sub_service')
            ->setDescriptionForEvent(fn(string $eventName) => "Client Sub-Service record has been {$eventName}");
    }

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
