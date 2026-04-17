<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToBranch;

class Meja extends Model
{
    use BelongsToBranch;
    //
     protected $guarded =[];
     public function pesanan(){
        return $this->hasMany(Pesanan::class, 'mejas_id');
     }
}
