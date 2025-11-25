<?php

namespace App\Livewire\Stock;

use App\Models\Ingredients;
use Livewire\Component;
use App\Models\RiwayatStock;

class StockAdd extends Component
{

    public $ingredient_id, $qty, $keterangan;
    public $current_stok;
    public $current_satuan;

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
            'qty' => $this->qty,
            'qty_before' => $before,
            'qty_after' => $after,
            'tipe' => 'in',
            'keterangan' => $this->keterangan ?? 'Restock'
        ]);

        $this->reset();
        $this->dispatch('showToast', type: 'success', message: 'Stock berhasil ditambah!');
    }
    public function render()
    {
        return view('livewire.stock.stock-add', [
            'ingredients' => Ingredients::orderBy('nama_bahan')->get()
        ]);
    }
}
