<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
   use SoftDeletes;
     protected $guarded =[];

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
