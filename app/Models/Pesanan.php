<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pesanan extends Model
{
    use SoftDeletes;
    //
    protected $guarded = [];
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


}
