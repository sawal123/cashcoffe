<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
     protected $guarded =[];

     public function pesanan(){
        return $this->hasMany(Pesanan::class, 'discount_id');
     }
}
