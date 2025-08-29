<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    //
    protected $guarded =[];

    public function category(){
        return $this->belongsTo(Category::class, 'categories_id', 'id');
     }
     public function pesananItems(){
        return $this->hasMany(PesananItem::class, 'menus_id');
     }
}
