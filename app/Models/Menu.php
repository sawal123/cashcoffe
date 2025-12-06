<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends Model
{
    use HasFactory;
    //
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class, 'categories_id', 'id');
    }
    public function pesananItems()
    {
        return $this->hasMany(PesananItem::class, 'menus_id');
    }

    // public function ingredients()
    // {
    //     return $this->belongsToMany(Ingredients::class, 'menu_ingredients')
    //         ->withPivot('qty')
    //         ->withTimestamps();
    // }
    public function ingredients()
    {
        return $this->belongsToMany(
            Ingredients::class,
            'menu_ingredients',
            'menu_id',        // foreign key di pivot untuk Menu
            'ingredient_id'   // foreign key di pivot untuk Ingredient ✅
        )->withPivot('qty');
    }


    public function stokTersedia(): bool
    {
        foreach ($this->ingredients as $ingredient) {
            if ($ingredient->stok < $ingredient->pivot->qty) {
                return false; // salah satu bahan habis
            }
        }
        return true;
    }
}
