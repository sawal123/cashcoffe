<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuPrice extends Model
{
    protected $fillable = ['menu_id', 'price_tier_id', 'sales_channel_id', 'harga', 'h_promo'];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function priceTier()
    {
        return $this->belongsTo(PriceTier::class);
    }

    public function salesChannel()
    {
        return $this->belongsTo(SalesChannel::class);
    }
}
