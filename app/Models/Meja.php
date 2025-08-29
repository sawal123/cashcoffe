<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meja extends Model
{
    //
     protected $guarded =[];
     public function pesanan(){
        return $this->hasMany(Pesanan::class, 'mejas_id');
     }
}
