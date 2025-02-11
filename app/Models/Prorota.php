<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Prorota extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'prorota';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff(array_keys($this->getAttributes()), ['created_at', 'updated_at', 'deleted_at']))
            ->useLogName('prorota')
            ->setDescriptionForEvent(fn(string $eventName) => "Prorota record has been {$eventName}");
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function prorotaDetail()
    {
        return $this->hasMany(ProrotaDetail::class);
    }
}
