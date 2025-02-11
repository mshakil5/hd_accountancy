<?php

namespace App\Models;

use App\Models\Client;
use App\Models\SubService;
use App\Models\ClientService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Service extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'service';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff(array_keys($this->getAttributes()), ['created_at', 'updated_at', 'deleted_at']))
            ->useLogName('service')
            ->setDescriptionForEvent(fn(string $eventName) => "Service record has been {$eventName}");
    }

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
