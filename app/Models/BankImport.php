<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a single uploaded bank statement (PDF / CSV).
 *
 * Provides the Eloquent entry point for bank_imports so controllers can use
 * findOrFail() instead of calling Eloquent-only methods on the query builder.
 */
class BankImport extends Model
{
    protected $guarded = [];

    protected $casts = [
        'transaction_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function scopeForAccount($query, int $bankAccountId)
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processing_status', '!=', 'processed');
    }
}
