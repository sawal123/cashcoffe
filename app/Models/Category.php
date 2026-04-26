<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //
     protected $guarded =[];
     protected $casts = [
        'is_active' => 'boolean'
     ];

     public function menus(){
        return $this->hasMany(Menu::class, 'categories_id', 'id');
     }
}
