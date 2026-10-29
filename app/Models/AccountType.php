<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'category', 'normal_balance', 'is_active'];

    public function subTypes()
    {
        return $this->hasMany(AccountSubType::class);
    }

    public function accountHeads()
    {
        return $this->hasMany(AccountHead::class);
    }
}
