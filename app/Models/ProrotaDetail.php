<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProrotaDetail extends Model
{
    use HasFactory;

    public function prorota()
    {
        return $this->belongsTo(Prorota::class);
    }
}
