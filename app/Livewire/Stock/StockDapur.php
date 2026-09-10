<?php

namespace App\Livewire\Stock;

use Livewire\Component;
use App\Models\Ingredients;
use Illuminate\Support\Facades\DB;
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
        $decoded = base64_decode((string) $id, true);

        if ($decoded === false || !ctype_digit((string) $decoded) || (int) $decoded <= 0) {
            $this->dispatch('showToast', message: 'Bahan tidak ditemukan.', type: 'error', title: 'Error');
            return;
        }

        $ingredientId = (int) $decoded;

        DB::transaction(function () use ($ingredientId) {
            $ingredient = Ingredients::lockForUpdate()->find($ingredientId);

            if (! $ingredient) {
                $this->dispatch('showToast', message: 'Bahan tidak ditemukan.', type: 'error', title: 'Error');
                return;
            }

            $usedByBaseRecipe = DB::table('menu_ingredients')
                ->where('ingredient_id', $ingredient->id)
                ->exists();

            $usedByVariantRecipe = DB::table('variant_option_ingredients')
                ->where('ingredient_id', $ingredient->id)
                ->exists();

            if ($usedByBaseRecipe && $usedByVariantRecipe) {
                $this->dispatch('showToast', message: 'Bahan tidak dapat dihapus karena masih digunakan dalam resep menu atau varian.', type: 'error', title: 'Error');
                return;
            }

            if ($usedByBaseRecipe) {
                $this->dispatch('showToast', message: 'Bahan tidak dapat dihapus karena masih digunakan dalam resep menu.', type: 'error', title: 'Error');
                return;
            }

            if ($usedByVariantRecipe) {
                $this->dispatch('showToast', message: 'Bahan tidak dapat dihapus karena masih digunakan dalam resep varian.', type: 'error', title: 'Error');
                return;
            }

            $ingredient->delete();
            $this->dispatch('showToast', message: 'Bahan berhasil dihapus.', type: 'success', title: 'Success');
        });
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
            'title' => 'Stok Dapur (Inventory)'
        ])->layout('layouts.app', ['title' => 'Stock Dapur']);
    }
}
