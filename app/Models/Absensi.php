<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'lokasi',
        'foto',
        'foto_keluar',
        'lokasi_keluar',
        'status',
        'keterangan',
    ];
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
