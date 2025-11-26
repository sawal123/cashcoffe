<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatuanBahan extends Model
{
    protected $guarded = [];
    public function ingredients()
    {
        return $this->hasMany(Ingredients::class, 'satuan_id');
    }
}
