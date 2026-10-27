<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A registered bank account belonging to a client.
 *
 * Accounts are registered once per business (client) and reused across
 * every bank statement upload. The user can either pick an existing account
 * or create a new one while uploading a statement.
 */
class BankAccount extends Model
{
    protected $table = 'reconciliation_bank_accounts';

    protected $fillable = [
        'client_id',
        'bank_name',
        'account_label',
        'account_last_four',
        'sort_code',
        'iban',
        'currency',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** The client (business) this account belongs to. */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}