<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DirectorInfo extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'director_info';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff(array_keys($this->getAttributes()), ['created_at', 'updated_at', 'deleted_at']))
            ->useLogName('director_info')
            ->setDescriptionForEvent(fn(string $eventName) => "Director Info record has been {$eventName}");
    }

    protected $booleanFields = [
        'utr_authorization',
        'directors_tax_return',
        'status'
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
