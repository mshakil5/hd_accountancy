<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'receipt_id', 'account_head_id', 'invoice_date', 'due_date',
        'invoice_number', 'net_amount', 'vat_amount', 'total_amount',
        'paid', 'payment_method', 'description',
    ];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function accountHead()
    {
        return $this->belongsTo(AccountHead::class);
    }
}
