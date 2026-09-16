<?php

namespace App\Models;

use App\Models\User;
use App\Models\Service;
use App\Models\ClientType;
use App\Models\ContactInfo;
use App\Models\BusinessInfo;
use App\Models\DirectorInfo;
use App\Models\ClientService;
use App\Models\ClientSubService;
use App\Models\RecentUpdate;
use App\Models\AccountancyFee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Laravel\Passport\HasApiTokens;

class Client extends Model
{
    use HasApiTokens, HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'client';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff(array_keys($this->getAttributes()), ['created_at', 'updated_at', 'deleted_at']))
            ->useLogName('client')
            ->setDescriptionForEvent(fn(string $eventName) => "Client record has been {$eventName}");
    }

    public function clientType(): BelongsTo
    {
        return $this->belongsTo(ClientType::class);
    }

    public function services(): HasMany
    {
        return $this->belongsToMany(Service::class, 'client_service', 'client_id', 'service_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function businessInfo(): BelongsTo
    {
        return $this->hasOne(BusinessInfo::class);
    }

    public function directorInfos(): HasMany
    {
        return $this->hasMany(DirectorInfo::class);
    }

    public function clientServices(): HasMany
    {
        return $this->hasMany(ClientService::class);
    }

    public function contactInfos(): HasMany
    {
        return $this->hasMany(ContactInfo::class);
    }

    public function clientSubServices(): HasMany
    {
        return $this->hasMany(ClientSubService::class, 'client_id', 'id');
    }

    public function recentUpdates(): HasMany
    {
        return $this->hasMany(RecentUpdate::class);
    }

    public function accountancyFee(): BelongsTo
    {
        return $this->hasOne(AccountancyFee::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(ClientProperty::class);
    }

    public function clientCredential(): BelongsTo
    {
        return $this->belongsTo(ClientCredential::class);
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(ClientCredential::class, 'client_credential_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'client_id');
    }
}