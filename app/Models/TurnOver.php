<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TurnOver extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    public function feature()
    {
        return $this->belongsTo(PackageFeature::class);
    }
}
