<?php

namespace App\Models;

use App\Models\User;
use App\Models\Service;
use App\Models\ClientType;
use App\Models\ContactInfo;
use App\Models\BusinessInfo;
use App\Models\DirectorInfo;
use App\Models\ClientService;
use App\Models\ContactInformation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function clientType()
    {
        return $this->belongsTo(ClientType::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'client_service','client_id', 'service_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function businessInfo()
    {
        return $this->hasOne(BusinessInfo::class);
    }

    public function directorInfos()
    {
        return $this->hasMany(DirectorInfo::class);
    }

    public function clientServices()
    {
        return $this->hasMany(ClientService::class);
    }

    public function contactInfos()
    {
        return $this->hasMany(ContactInfo::class);
    }

    public function clientSubServices()
    {
        return $this->hasMany(ClientSubService::class, 'client_id', 'id');
    }

}
