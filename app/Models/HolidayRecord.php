<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayRecord extends Model
{
    use HasFactory;

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

}
