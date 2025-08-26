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
}
