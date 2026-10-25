<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'qualification_data' => 'array',
        'human_takeover' => 'boolean',
        'qualified_at' => 'datetime',
        'automation_completed_at' => 'datetime',
        'contacted_at' => 'datetime',
        'last_inbound_at' => 'datetime',
        'last_outbound_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(LeadMessage::class)->orderBy('id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isWithinWhatsAppWindow(): bool
    {
        return $this->last_inbound_at && $this->last_inbound_at->greaterThan(now()->subHours(24));
    }

    public function isAutomationComplete(): bool
    {
        return $this->automation_completed_at !== null;
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('sales_status', ['WON', 'LOST', 'CLOSED']);
    }
};
