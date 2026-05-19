<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'is_double_shift',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userShift()
    {
        return $this->belongsTo(UserShift::class, 'shift_id', 'shift_id');
    }
}
