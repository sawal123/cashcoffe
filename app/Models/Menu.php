<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

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

    public function menuPrices()
    {
        return $this->hasMany(MenuPrice::class);
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

    /**
     * Relasi ketersediaan menu di berbagai cabang.
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_menu')
            ->withPivot('is_available')
            ->withTimestamps();
        /**
         * Varian yang tersedia untuk menu ini (dikelompokkan per grup).
         */
    }

    public function variantGroups()
    {
        return $this->belongsToMany(VariantGroup::class, 'menu_variant_group');
    }
}
