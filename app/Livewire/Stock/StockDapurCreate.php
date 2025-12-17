<?php

namespace App\Livewire\Stock;

use Livewire\Component;
use App\Models\Ingredients;
use App\Models\SatuanBahan;
use Illuminate\Support\Str;
use App\Models\RiwayatStock;
use App\Models\MenuIngredients;
use App\Livewire\Menu\MenuIngredient;

class StockDapurCreate extends Component
{
    public $nama_bahan;
    public $stok;
    public $satuan_id;

    public $newSatuan;

    public $satuans, $stockId, $ingredient_id, $qty, $keterangan, $current_stok, $current_satuan;

    public function mount($stockId = null)
    {
        $this->loadSatuan();
        $this->stockId = $stockId;
        // dd($stockId);
        if ($stockId) {
            $bahan = Ingredients::findOrFail($stockId);
            // dd($bahan->stok);
            $this->nama_bahan = $bahan->nama_bahan;
            $this->satuan_id = $bahan->satuan_id;
            $this->stok = intval($bahan->stok);
        }
    }


    public function loadSatuan()
    {
        $this->satuans = SatuanBahan::orderBy('nama_satuan')->get();
    }

    public function simpan()
    {
        $this->validate([
            'nama_bahan' => 'required',
            'stok'       => 'required|numeric|min:0',
            'satuan_id'  => 'required|exists:satuan_bahans,id',
        ]);

        $ingredient = Ingredients::create([
            'nama_bahan' => $this->nama_bahan,
            'stok'       => $this->stok,
            'satuan_id'  => $this->satuan_id,
        ]);

        RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'kode' => strtoupper('IN-' . Str::random(6)),
            'qty'           => $this->stok,
            'keterangan'    => 'Stok awal',
            'tipe'          => 'in'
        ]);

        $this->reset(['nama_bahan', 'stok', 'satuan_id']);

        $this->dispatch('showToast', type: 'success', message: 'Bahan berhasil disimpan!');
    }

    public function update($id)
    {
        $this->validate([
            'nama_bahan' => 'required',
            'stok'       => 'required|numeric|min:0',
            'satuan_id'  => 'required|exists:satuan_bahans,id',
        ]);

        $bahan = Ingredients::findOrFail($id);
        $bahan->update([
            'nama_bahan' => $this->nama_bahan,
            'stok'       => $this->stok,
            'satuan_id'  => $this->satuan_id,
        ]);

        $this->dispatch('showToast', type: 'success', message: 'Bahan berhasil diupdate!');
    }

    public function saveSatuan()
    {
        $this->validate([
            'newSatuan' => 'required|string|min:2'
        ]);

        SatuanBahan::create([
            'nama_satuan' => $this->newSatuan
        ]);

        $this->reset('newSatuan');
        $this->loadSatuan();

        $this->dispatch('showToast', type: 'success', message: 'Satuan berhasil ditambahkan!');
    }

    public function deleteSatuan($id)
    {
        SatuanBahan::find($id)?->delete();
        $this->loadSatuan();

        $this->dispatch('showToast', type: 'success', message: 'Satuan berhasil dihapus!');
    }

    public function render()
    {
        return view('livewire.stock.stock-dapur-create');
    }
}
