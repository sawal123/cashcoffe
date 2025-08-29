<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
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
}
