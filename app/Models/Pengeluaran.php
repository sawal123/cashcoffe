<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToBranch;

class Pengeluaran extends Model
{
    use BelongsToBranch;
    protected $guarded =[];
}
