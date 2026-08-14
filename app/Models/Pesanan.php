<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToBranch;

class Pesanan extends Model
{
    use SoftDeletes, BelongsToBranch;

    public const STATUS_DIPROSES = 'diproses';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIBATALKAN = 'dibatalkan';

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

    public function canTransitionTo(string $targetStatus): bool
    {
        if ($this->status === self::STATUS_DIPROSES) {
            return in_array($targetStatus, [self::STATUS_SELESAI, self::STATUS_DIBATALKAN], true);
        }

        return false;
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DIPROSES;
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [self::STATUS_SELESAI, self::STATUS_DIBATALKAN], true);
    }

    public function decrementDiscountUsageOnCancellation(): void
    {
        if ($this->discount_id && $this->discount) {
            if ($this->discount->scope === 'global' && $this->discount_value > 0 && $this->discount->digunakan > 0) {
                $this->discount->decrement('digunakan');
            }
        }
    }

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
