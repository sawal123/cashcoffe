<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gudang extends Model
{
    use HasFactory;
    protected $fillable = [
        'nama_bahan',
        'satuan',
        'stok',
        'harga_satuan',
        'minimum_stok',
        'keterangan',
    ];

     public function riwayats()
    {
        return $this->hasMany(GudangRiwayat::class);
    }
}
