<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'kode_aset',
        'nama_aset',
        'qty',
        'kategori',
        'kondisi',
        'tanggal_pembelian',
        'harga_beli',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_pembelian' => 'date',
        'harga_beli' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
