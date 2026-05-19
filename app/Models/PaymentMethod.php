<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = ['nama_metode', 'kode_metode', 'is_active'];

    public function pesanans()
    {
        return $this->hasMany(Pesanan::class, 'payment_method_id');
    }
}
