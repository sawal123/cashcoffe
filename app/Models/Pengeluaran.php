<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToBranch;

class Pengeluaran extends Model
{
    use SoftDeletes, BelongsToBranch;
    protected $guarded =[];

    public function satuanBahan()
    {
        return $this->belongsTo(SatuanBahan::class, 'satuan_id');
    }
}
