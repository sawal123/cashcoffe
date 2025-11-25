<?php

namespace App\Livewire\Stock;

use Livewire\Component;
use App\Models\Ingredients;

class StockDapur extends Component
{
    public function render()
    {
        return view('livewire.stock.stock-dapur', [
            'items' => Ingredients::orderBy('nama_bahan')->get(),
        ]);
    }
}
