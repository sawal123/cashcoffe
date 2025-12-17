<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredients extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_ingredients')
            ->withPivot('qty')
            ->withTimestamps();
    }

    public function stocks()
    {
        return $this->hasMany(RiwayatStock::class, 'ingredient_id');
    }

    public function getTotalStokAttribute()
    {
        return $this->stocks->sum('qty');
    }

    public function satuan()
    {
        return $this->belongsTo(SatuanBahan::class, 'satuan_id');
    }
}
