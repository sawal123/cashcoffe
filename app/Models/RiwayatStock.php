<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiwayatStock extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function ingredient()
    {
        return $this->belongsTo(Ingredients::class, 'ingredient_id');
    }
}
