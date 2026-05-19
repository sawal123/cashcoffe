<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserShift extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
        'is_double_shift' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
