<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BusinessInfo extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'business_info';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff(array_keys($this->getAttributes()), ['created_at', 'updated_at', 'deleted_at']))
            ->useLogName('business_info')
            ->setDescriptionForEvent(fn(string $eventName) => "Business Info record has been {$eventName}");
    }

    protected $booleanFields = [
        'vat_authorization',
        'paye_authorization',
        'ct_authorization'
    ];

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->booleanFields)) {
            return $value ? 'Yes' : 'No';
        }

        return $value;
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
