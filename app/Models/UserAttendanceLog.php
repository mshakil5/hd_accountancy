<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class UserAttendanceLog extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'user_attendance_log';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff(array_keys($this->getAttributes()), ['created_at', 'updated_at', 'deleted_at']))
            ->useLogName('user_attendance_log')
            ->setDescriptionForEvent(fn(string $eventName) => "User  Attendance Log record has been {$eventName}");
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prorotaDetail()
    {
        return $this->belongsTo(ProrotaDetail::class);
    }

}
