<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Opsi-opsi yang ada di dalam grup ini.
     */
    public function options()
    {
        return $this->hasMany(VariantOption::class);
    }

    /**
     * Menu yang menggunakan grup varian ini.
     */
    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_variant_group');
    }
}
