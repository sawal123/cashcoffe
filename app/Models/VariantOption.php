<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantOption extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Grup tempat opsi ini bernaung.
     */
    public function group()
    {
        return $this->belongsTo(VariantGroup::class, 'variant_group_id');
    }

    /**
     * Relasi ke item pesanan yang memilih opsi ini.
     */
    public function pesananItems()
    {
        return $this->belongsToMany(PesananItem::class, 'pesanan_item_variants');
    }

    /**
     * Komposisi bahan baku tambahan untuk opsi varian ini.
     * Contoh: Varian "Large" membutuhkan +10g kopi, +50ml susu.
     */
    public function ingredients()
    {
        return $this->belongsToMany(
            Ingredients::class,
            'variant_option_ingredients',
            'variant_option_id',
            'ingredient_id'
        )->withPivot('qty')->withTimestamps();
    }
}
