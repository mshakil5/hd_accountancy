<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadMessage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'interactive_payload' => 'array',
        'raw_payload' => 'array',
        'provider_timestamp' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
};
