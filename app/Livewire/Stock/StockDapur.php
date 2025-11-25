<?php

namespace App\Livewire\Stock;

use Livewire\Component;
use App\Models\Ingredients;
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

    public function render()
    {
        $items = Ingredients::where('nama_bahan', 'like', '%' . $this->search . '%')
            ->orderBy('nama_bahan')
            ->paginate($this->perPage);

        return view('livewire.stock.stock-dapur', [
            'items' => $items,
            'perPage' => $this->perPage,
        ]);
    }
}
