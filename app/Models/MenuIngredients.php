<?php

namespace App\Models;

use App\Models\Ingredients;
use Illuminate\Database\Eloquent\Model;

class MenuIngredients extends Model
{
    protected $guarded = [];

    // protected $fillable = ['menu_id', 'ingredient_id', 'qty'];

    public function ingredient()
    {
        return $this->belongsTo(Ingredients::class);
    }
}
