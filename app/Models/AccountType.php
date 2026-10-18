<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'category', 'normal_balance', 'is_active', 'account_head_id', 'client_credential_id'];

    public function accountHead()
    {
        return $this->belongsTo(AccountHead::class);
    }

    public function clientCredential()
    {
        return $this->belongsTo(ClientCredential::class);
    }
}
