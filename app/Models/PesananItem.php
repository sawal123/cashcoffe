<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PesananItem extends Model
{
    use SoftDeletes;
    //
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

    public function varian()
    {
        return $this->belongsTo(MenuVarian::class, 'varian_id');
    }
}
