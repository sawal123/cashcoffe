<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceTier extends Model
{
    protected $fillable = ['nama_tier', 'is_active'];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function menuPrices()
    {
        return $this->hasMany(MenuPrice::class);
    }
}
