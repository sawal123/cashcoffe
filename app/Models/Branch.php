<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_cabang',
        'nama_cabang',
        'alamat',
        'no_telp',
        'is_active',
        'price_tier_id',
        'latitude',
        'longitude',
        'radius',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function priceTier()
    {
        return $this->belongsTo(PriceTier::class);
    }

    public function mejas()
    {
        return $this->hasMany(Meja::class);
    }

    public function ingredients()
    {
        return $this->hasMany(Ingredients::class);
    }

    public function pesanans()
    {
        return $this->hasMany(Pesanan::class);
    }

    public function pengeluarans()
    {
        return $this->hasMany(Pengeluaran::class);
    }

    public function riwayatStocks()
    {
        return $this->hasMany(RiwayatStock::class);
    }

    /**
     * Relasi ketersediaan menu khusus untuk cabang ini.
     */
    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'branch_menu')
            ->withPivot('is_available')
            ->withTimestamps();
    }
}
