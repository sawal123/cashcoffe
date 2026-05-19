<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesChannel extends Model
{
    use HasFactory;

    protected $fillable = ['nama_channel', 'is_active'];

    public function menuPrices()
    {
        return $this->hasMany(MenuPrice::class);
    }
    
    public function variantPrices()
    {
        return $this->hasMany(VariantPrice::class);
    }

    public function pesanans()
    {
        return $this->hasMany(Pesanan::class);
    }
}
