<?php

namespace App\Livewire\Stock;

use Livewire\Component;
use App\Models\Ingredients;
use Faker\Provider\Base;
use Livewire\WithPagination;

class StockDapur extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    protected $paginationTheme = 'tailwind'; // atau bootstrap sesuai project

    // Reset halaman ketika pencarian berubah
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function deleteIngredient($id)
    {
        // dd($id);
        $ingredient = Ingredients::find(base64_decode($id));
        if ($ingredient) {
            $ingredient->delete();
            $this->dispatch('showToast', message: 'Bahan berhasil dihapus.', type: 'success', title: 'Success');
        } else {
            $this->dispatch('showToast', message: 'Bahan tidak ditemukan.', type: 'error', title: 'Error');
        }
    }

    public function render()
    {

        $items = Ingredients::with('satuan')
            ->withSum([
                'stocks as digunakan' => function ($q) {
                    $q->where('tipe', 'out');
                }
            ], 'qty')
            ->where('nama_bahan', 'like', '%' . $this->search . '%')
            ->orderBy('nama_bahan')
            ->paginate($this->perPage);

        return view('livewire.stock.stock-dapur', [
            'items' => $items,
            'perPage' => $this->perPage,
        ]);
    }
}
