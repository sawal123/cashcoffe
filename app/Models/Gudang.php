<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gudang extends Model
{
    use HasFactory;
    protected $fillable = [
        'branch_id',
        'nama_bahan',
        'satuan',
        'stok',
        'harga_satuan',
        'minimum_stok',
        'keterangan',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        // Legacy records without branch_id remain superadmin-only until assigned.
        return $user->hasRole('superadmin')
            ? $query
            : $query->where('branch_id', $user->branch_id ?? 0);
    }

    public function riwayats()
    {
        return $this->hasMany(GudangRiwayat::class);
    }
}
