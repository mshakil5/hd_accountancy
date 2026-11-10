<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Explicit Ad ID / Campaign ID to qualification intent mapping.
 *
 * Campaign intent must never be guessed from ad marketing copy: HD's generic
 * advert mentions "accounts", "VAT", "payroll" and "accountant", which would
 * route the same advert to the wrong qualification path.
 */
class MetaAdIntent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
