<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    protected $fillable = [
        'user_id',
        'absensi_id',
        'tanggal',
        'jam_masuk_baru',
        'jam_keluar_baru',
        'alasan',
        'status',
        'approved_by',
        'catatan_admin',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function absensi()
    {
        return $this->belongsTo(Absensi::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
