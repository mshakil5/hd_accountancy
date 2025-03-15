<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkTime extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function clientSubService()
    {
        return $this->belongsTo(ClientSubService::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

}
