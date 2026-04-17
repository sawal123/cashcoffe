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
}
