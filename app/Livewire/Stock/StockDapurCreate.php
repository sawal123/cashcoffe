<?php

namespace App\Livewire\Stock;

use Livewire\Component;
use App\Livewire\Menu\MenuIngredient;
use App\Models\Ingredients;
use App\Models\MenuIngredients;
use App\Models\RiwayatStock;

class StockDapurCreate extends Component
{
    public $nama_bahan;
    public $stok;
    public $satuan = 'gr';

    public function simpan()
    {
        $this->validate([
            'nama_bahan' => 'required',
            'stok' => 'required|numeric|min:0',
            'satuan' => 'required',
        ]);

         $ingredient = Ingredients::create([
            'nama_bahan' => $this->nama_bahan,
            'stok' => $this->stok,
            'satuan' => $this->satuan,
        ]);

        // masukkan stok awal sebagai log
        RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'qty' => $this->stok,
            'keterangan' => 'Stok awal',
            'tipe' => 'in'
        ]);

        $this->reset();
        $this->dispatch('showToast', type: 'success', message: 'Stock berhasil disimpan!');
    }
    public function render()
    {
        return view('livewire.stock.stock-dapur-create');
    }
}
