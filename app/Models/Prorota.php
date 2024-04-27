<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prorota extends Model
{
    use HasFactory;

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function prorotaDetail()
    {
        return $this->hasMany(ProrotaDetail::class);
    }
}
