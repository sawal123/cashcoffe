<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'member_only' => 'boolean',
        'tanggal_mulai' => 'date',
        'tanggal_akhir' => 'date',
    ];

    public function isMemberOnly(): bool
    {
        return (bool) $this->member_only;
    }

    public function canBeUsedBy(?Member $member): bool
    {
        return ! $this->isMemberOnly() || $member !== null;
    }

    public function canBeUsedByMemberId(?int $memberId): bool
    {
        return ! $this->isMemberOnly() || $memberId !== null;
    }

     public function pesanan(){
        return $this->hasMany(Pesanan::class, 'discount_id');
     }

     public function branch()
     {
         return $this->belongsTo(Branch::class);
     }

     public function priceTier()
     {
         return $this->belongsTo(PriceTier::class);
     }

     public function discountItems()
     {
         return $this->hasMany(DiscountItem::class);
     }
}
