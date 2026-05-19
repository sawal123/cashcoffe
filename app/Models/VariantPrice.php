<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantPrice extends Model
{
    use HasFactory;

    protected $fillable = ['variant_option_id', 'price_tier_id', 'sales_channel_id', 'extra_price'];

    public function variantOption()
    {
        return $this->belongsTo(VariantOption::class);
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
