<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id', 'receipt_number', 'receipt_date',
        'notes', 'supplier', 'status', 'created_by',
    ];

    protected $casts = ['receipt_date' => 'date'];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function files()
    {
        return $this->hasMany(ReceiptFile::class, 'receipt_id');
    }

    public function detail()
    {
        return $this->hasOne(ReceiptDetail::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}