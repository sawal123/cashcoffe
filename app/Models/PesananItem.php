<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PesananItem extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanans_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menus_id');
    }

    public function menus()
    {
        return $this->belongsTo(Menu::class, 'menus_id');
    }

    /**
     * Opsi varian yang dipilih untuk item pesanan ini.
     */
    public function variants()
    {
        return $this->belongsToMany(
            VariantOption::class,
            'pesanan_item_variants',
            'pesanan_item_id',
            'variant_option_id'
        )->withTimestamps();
    }
}
