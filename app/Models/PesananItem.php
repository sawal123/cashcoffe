<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananItem extends Model
{
    //
     protected $guarded =[];

      public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanans_id');
    }
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menus_id');
    }
}
