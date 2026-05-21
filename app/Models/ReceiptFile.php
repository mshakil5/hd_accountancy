<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptFile extends Model
{
    protected $fillable = [
        'receipt_id', 'file_path', 'file_name',
        'file_type', 'mime_type', 'file_size',
    ];

    public function getUrlAttribute()
    {
        return asset('images/receipts/' . basename($this->file_path));
    }
}