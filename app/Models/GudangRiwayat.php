<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GudangRiwayat extends Model
{
     use HasFactory;

    protected $fillable = [
        'gudang_id',
        'tipe',
        'jumlah',
        'stok_sebelum',
        'stok_sesudah',
        'harga_satuan',
        'total_harga',
        'keterangan',
        'user_id',
    ];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
