<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactInfo extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $fillable = [
        'client_id',
        'greeting',
        'first_name',
        'last_name',
        'job_title',
        'email',
        'phone',
        'created_by',
        'updated_by',
    ];
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
