<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountHead extends Model
{
    use HasFactory;
    protected $fillable = ['account_type_id', 'tax_rate_id', 'code', 'name', 'description', 'is_active'];

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }
}
