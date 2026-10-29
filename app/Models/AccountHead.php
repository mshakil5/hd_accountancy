<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountHead extends Model
{
    use HasFactory;

    protected $fillable = ['account_type_id', 'account_sub_type_id', 'client_credential_id', 'code', 'name', 'normal_balance', 'is_active'];

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function accountSubType()
    {
        return $this->belongsTo(AccountSubType::class);
    }

    public function clientCredential()
    {
        return $this->belongsTo(ClientCredential::class);
    }

    /**
     * Debit/credit nature of the head, falling back to its type's default.
     */
    public function normalBalance(): string
    {
        return $this->normal_balance ?: ($this->accountType?->normal_balance ?? 'debit');
    }
}
