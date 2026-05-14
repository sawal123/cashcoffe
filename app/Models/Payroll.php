<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'user_id',
        'bulan',
        'tahun',
        'total_hadir',
        'total_terlambat',
        'total_izin',
        'total_alpha',
        'gaji_pokok',
        'total_tunjangan',
        'total_potongan',
        'take_home_pay',
        'status_pembayaran',
        'tanggal_pembayaran',
    ];

    protected $casts = [
        'tanggal_pembayaran' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
