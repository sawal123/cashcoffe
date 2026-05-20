<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'user_id',
        'periode_mulai',
        'periode_selesai',
        'gaji_pokok',
        'insentif_double_shift',
        'potongan_alpha',
        'potongan_telat',
        'potongan_tidak_clock_out',
        'gaji_bersih',
    ];

    protected $casts = [
        'periode_mulai' => 'date',
        'periode_selesai' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
