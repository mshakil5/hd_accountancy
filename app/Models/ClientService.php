<?php

namespace App\Models;

use App\Models\Client;
use App\Models\Service;
use App\Models\ClientSubService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ClientService extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'client_service';
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'client_service';

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff(array_keys($this->getAttributes()), ['created_at', 'updated_at', 'deleted_at']))
            ->useLogName('client_service')
            ->setDescriptionForEvent(fn(string $eventName) => "Client Service record has been {$eventName}");
    }

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

    public function messages()
    {
        return $this->hasMany(ServiceMessage::class, 'client_service_id', 'id');
    }

    public function directorInfo()
    {
        return $this->belongsTo(DirectorInfo::class, 'director_info_id');
    }
}
