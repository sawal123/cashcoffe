<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatStock extends Model
{
    protected $guarded = [];

    public function ingredient()
    {
        return $this->belongsTo(Ingredients::class, 'ingredient_id');
    }
}
