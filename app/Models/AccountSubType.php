<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountSubType extends Model
{
    use HasFactory;

    protected $fillable = ['account_type_id', 'name', 'is_active'];

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function accountHeads()
    {
        return $this->hasMany(AccountHead::class);
    }
}
