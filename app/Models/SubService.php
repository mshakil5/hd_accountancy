<?php

namespace App\Models;

use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubService extends Model
{

    protected $guarded = [];

    use HasFactory;
    use SoftDeletes;

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
