<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffHolidayType extends Model
{
    use HasFactory;

    public function holidayType()
    {
        return $this->belongsTo(HolidayType::class);
    }
}
