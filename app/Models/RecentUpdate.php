<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RecentUpdate extends Model
{
    use HasFactory, LogsActivity;
    
    protected $guarded = [];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'recent_update';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff(array_keys($this->getAttributes()), ['created_at', 'updated_at']))
            ->useLogName('recent_update')
            ->setDescriptionForEvent(fn(string $eventName) => "Recent Update record has been {$eventName}");
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
