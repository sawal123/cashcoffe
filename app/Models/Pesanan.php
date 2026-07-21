<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToBranch;

class Pesanan extends Model
{
    use SoftDeletes, BelongsToBranch;

    protected $fillable = [
        'kode',
        'nama',
        'user_id',
        'member_id',
        'mejas_id',
        'discount_id',
        'status',
        'metode_pembayaran',
        'payment_method_id',
        'sales_channel_id',
        'total',
        'total_profit',
        'discount_value',
        'catatan',
        'uang_tunai',
        'kembalian'
    ];

    public function items()
    {
        return $this->hasMany(PesananItem::class, 'pesanans_id');
    }
    public function meja()
    {
        return $this->belongsTo(Meja::class, 'mejas_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function salesChannel()
    {
        return $this->belongsTo(SalesChannel::class, 'sales_channel_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
