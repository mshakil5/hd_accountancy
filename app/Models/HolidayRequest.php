<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'holiday_type',
        'start_date',
        'end_date',
        'comment',
        'admin_note',
        'total_day',
        'status',
        'staff_notification',
        'created_by',
        'updated_by'
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

}
