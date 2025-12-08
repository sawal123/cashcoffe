<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IzinAbsensi extends Model
{
    protected $table = 'izin_absensis';

    protected $fillable = [
        'user_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'jenis',
        'alasan',
        'bukti',
        'status',
        'approved_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public  function absensis()
    {
        return $this->hasMany(Absensi::class);
    }
}
