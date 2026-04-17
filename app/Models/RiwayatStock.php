<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToBranch;

class RiwayatStock extends Model
{
    use SoftDeletes, BelongsToBranch;
    protected $guarded = [];

    public function ingredient()
    {
        return $this->belongsTo(Ingredients::class, 'ingredient_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }
}
