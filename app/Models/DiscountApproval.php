<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'kasir_id',
        'discount_id',
        'status',
    ];

    // Relasi ke tabel Users (Kasir)
    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    // Relasi ke tabel Discounts
    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }
}
