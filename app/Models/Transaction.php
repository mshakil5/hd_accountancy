<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

     protected $fillable = [
        'transaction_uid', 'receipt_id', 'account_head_id', 'type',
        'amount', 'tax_percent', 'tax_amount', 'vat_amount', 'total_amount',
        'payment_method', 'parent_id', 'created_by',
    ];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function accountHead()
    {
        return $this->belongsTo(AccountHead::class);
    }

    public function parent()
    {
        return $this->belongsTo(Transaction::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Transaction::class, 'parent_id');
    }
}
