<?php

namespace App\Livewire\Stock;

use App\Models\Ingredients;
use App\Models\RiwayatStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class StockAdd extends Component
{
    public $stockId, $submit;        // dari controller: create(null) / edit($id)
    public $ingredient_id;
    public $qty;
    public $keterangan;

    public $current_stok;
    public $current_satuan;

    public function mount($stockId = null)
    {
        $this->stockId = $stockId;

        if ($stockId) {
            $riwayatId = is_numeric($stockId) ? (int) $stockId : (int) base64_decode($stockId);
            $riwayat = RiwayatStock::findOrFail($riwayatId);

            $this->ingredient_id = $riwayat->ingredient_id;
            $this->qty = intval($riwayat->qty);
            $this->keterangan = $riwayat->keterangan;

            $bahan = Ingredients::find($riwayat->ingredient_id);
            $this->current_stok = $bahan ? $bahan->stok : null;
            $this->current_satuan = $bahan && $bahan->satuan ? $bahan->satuan->nama_satuan : null;
        }
    }
    public function updatedIngredientId($value)
    {
        if ($value) {
            $bahan = Ingredients::find($value);
            if ($bahan) {
                $this->current_stok = $bahan->stok;
                $this->current_satuan = $bahan->satuan ? $bahan->satuan->nama_satuan : null;
            } else {
                $this->current_stok = null;
                $this->current_satuan = null;
            }
        } else {
            $this->current_stok = null;
            $this->current_satuan = null;
        }
    }

    // --------------------
    // CREATE STOCK
    // --------------------
    public function tambahStok()
    {
        $this->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'qty' => 'required|numeric|min:1',
        ]);

        $bahan = Ingredients::find($this->ingredient_id);

        if (! $bahan) {
            $this->addError('ingredient_id', 'Bahan baku tidak ditemukan.');
            $this->dispatch('showToast', type: 'error', message: 'Bahan baku tidak ditemukan.');
            return;
        }

        if ($bahan->branch_id === null) {
            $this->addError('ingredient_id', 'Bahan baku belum memiliki cabang. Perbaiki data cabang terlebih dahulu sebelum menambah stok.');
            $this->dispatch('showToast', type: 'error', message: 'Bahan baku belum memiliki cabang. Perbaiki data cabang terlebih dahulu sebelum menambah stok.');
            return;
        }

        DB::transaction(function () use ($bahan) {
            $before = $bahan->stok;
            $after = $before + $this->qty;

            $bahan->update([
                'stok' => $after
            ]);

            RiwayatStock::create([
                'ingredient_id' => $bahan->id,
                'branch_id' => $bahan->branch_id,
                'kode' => strtoupper('IN-' . Str::random(6)),
                'qty' => $this->qty,
                'qty_before' => $before,
                'qty_after' => $after,
                'tipe' => 'in',
                'keterangan' => $this->keterangan ?? 'Restock'
            ]);
        });

        $this->reset(['ingredient_id', 'qty', 'keterangan', 'current_stok', 'current_satuan']);
        $this->dispatch('showToast', type: 'success', message: 'Stock berhasil ditambah!');
    }

    // --------------------
    // UPDATE STOCK (EDIT)
    // --------------------
    public function updateStok()
    {
        $this->validate([
            'qty' => 'required|numeric|min:1',
        ]);

        $riwayatId = is_numeric($this->stockId) ? (int) $this->stockId : (int) base64_decode($this->stockId);
        $riwayat = RiwayatStock::findOrFail($riwayatId);
        $bahan = Ingredients::find($riwayat->ingredient_id);

        if (! $bahan) {
            $this->addError('ingredient_id', 'Bahan baku tidak ditemukan.');
            $this->dispatch('showToast', type: 'error', message: 'Bahan baku tidak ditemukan.');
            return;
        }

        if ($bahan->branch_id === null) {
            $this->addError('ingredient_id', 'Bahan baku belum memiliki cabang. Perbaiki data cabang terlebih dahulu sebelum menambah stok.');
            $this->dispatch('showToast', type: 'error', message: 'Bahan baku belum memiliki cabang. Perbaiki data cabang terlebih dahulu sebelum menambah stok.');
            return;
        }

        DB::transaction(function () use ($riwayat, $bahan) {
            // Hitung ulang stok: kembalikan ke before, lalu tambahkan qty baru
            $stokKembali = $bahan->stok - $riwayat->qty;
            $stokBaru = $stokKembali + $this->qty;

            $bahan->update([
                'stok' => $stokBaru
            ]);

            $riwayat->update([
                'branch_id' => $bahan->branch_id,
                'qty' => $this->qty,
                'qty_after' => $stokBaru,
                'keterangan' => $this->keterangan
            ]);
        });

        $this->dispatch('showToast', type: 'success', message: 'Stock berhasil diperbarui!');
    }

    public function render()
    {
        $title = $this->stockId ? 'Edit Input Stok' : 'Input Stok Masuk';
        return view('livewire.stock.stock-add', [
            'ingredients' => Ingredients::orderBy('nama_bahan')->get(),
            'title' => $title,
            'backUrl' => '/riwayat-stock'
        ])->layout('layouts.app', ['title' => $title]);
    }
}
