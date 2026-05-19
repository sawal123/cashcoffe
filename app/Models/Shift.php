<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $guarded = [];
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function userShifts()
    {
        return $this->hasMany(UserShift::class);
    }
}
