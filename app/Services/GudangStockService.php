<?php

namespace App\Services;

use App\Models\Gudang;
use App\Models\GudangRiwayat;

class GudangStockService
{
    public function applyMovement(float $stock, string $type, float $quantity): float
    {
        return $type === 'masuk'
            ? $stock + $quantity
            : $stock - $quantity;
    }

    public function rebuild(Gudang $gudang, ?float $openingStock = null): float
    {
        $riwayats = GudangRiwayat::where('gudang_id', $gudang->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($riwayats->isEmpty()) {
            $finalStock = $openingStock ?? 0;
            $gudang->update(['stok' => $finalStock]);

            return $finalStock;
        }

        $runningStock = $openingStock ?? (float) $riwayats->first()->stok_sebelum;

        foreach ($riwayats as $riwayat) {
            $stokSebelum = $runningStock;
            $stokSesudah = $this->applyMovement(
                $stokSebelum,
                $riwayat->tipe,
                (float) $riwayat->jumlah
            );

            $riwayat->update([
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
            ]);

            $runningStock = $stokSesudah;
        }

        $gudang->update(['stok' => $runningStock]);

        return $runningStock;
    }
}
