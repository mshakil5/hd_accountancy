<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessInfo extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
