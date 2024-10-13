<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Master extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function softcode()
    {
        return $this->belongsTo(Softcode::class, 'softcode_id');
    }
}
