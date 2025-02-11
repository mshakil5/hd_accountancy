<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProrotaDetail extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'prorota_detail';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff(array_keys($this->getAttributes()), ['created_at', 'updated_at', 'deleted_at']))
            ->useLogName('prorota_detail')
            ->setDescriptionForEvent(fn(string $eventName) => "Prorota Detail record has been {$eventName}");
    }

    public function prorota()
    {
        return $this->belongsTo(Prorota::class);
    }
}
