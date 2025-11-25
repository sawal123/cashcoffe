<?php

namespace App\Livewire\Stock;

use Livewire\Component;
use App\Models\Ingredients;
use Illuminate\Support\Str;
use App\Models\RiwayatStock;

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
            $riwayat = RiwayatStock::findOrFail($stockId);

            $this->ingredient_id = $riwayat->ingredient_id;
            $this->qty = intval($riwayat->qty);
            $this->keterangan = $riwayat->keterangan;

            $bahan = Ingredients::find($riwayat->ingredient_id);
            $this->current_stok = $bahan->stok;
            $this->current_satuan = $bahan->satuan;
        }
    }
    public function updatedIngredientId($value)
    {
        if ($value) {
            $bahan = Ingredients::find($value);
            $this->current_stok = $bahan->stok;
            $this->current_satuan = $bahan->satuan;
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
        $bahan = Ingredients::find($this->ingredient_id);

        $before = $bahan->stok;
        $after = $before + $this->qty;

        $bahan->update([
            'stok' => $after
        ]);

        RiwayatStock::create([
            'ingredient_id' => $bahan->id,
            'kode' => strtoupper('IN-' . Str::random(6)),
            'qty' => $this->qty,
            'qty_before' => $before,
            'qty_after' => $after,
            'tipe' => 'in',
            'keterangan' => $this->keterangan ?? 'Restock'
        ]);

        $this->reset();
        $this->dispatch('showToast', type: 'success', message: 'Stock berhasil ditambah!');
    }

    // --------------------
    // UPDATE STOCK (EDIT)
    // --------------------
    public function updateStok()
    {
        $riwayat = RiwayatStock::findOrFail($this->stockId);
        $bahan = Ingredients::find($riwayat->ingredient_id);

        // Hitung ulang stok: kembalikan ke before, lalu tambahkan qty baru
        $stokKembali = $bahan->stok - $riwayat->qty;
        $stokBaru = $stokKembali + $this->qty;

        $bahan->update([
            'stok' => $stokBaru
        ]);

        $riwayat->update([
            'qty' => $this->qty,
            'qty_after' => $stokBaru,
            'keterangan' => $this->keterangan
        ]);

        $this->dispatch('showToast', type: 'success', message: 'Stock berhasil diperbarui!');
    }

    public function render()
    {
        return view('livewire.stock.stock-add', [
            'ingredients' => Ingredients::orderBy('nama_bahan')->get()
        ]);
    }
}
