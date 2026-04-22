<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountItem extends Model
{
    protected $guarded = [];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function model()
    {
        return $this->morphTo();
    }
}