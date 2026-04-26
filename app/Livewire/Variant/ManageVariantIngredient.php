<?php

namespace App\Livewire\Variant;

use App\Models\Ingredients;
use App\Models\VariantOption;
use Livewire\Component;

class ManageVariantIngredient extends Component
{
    public VariantOption $variantOption;

    // Daftar bahan yang sudah ditambahkan ke resep varian ini
    public array $recipeRows = [];

    // Untuk input tambah bahan baru
    public $newIngredientId = '';
    public $newQty = '';

    public function mount(string $id): void
    {
        $this->variantOption = VariantOption::with('ingredients')->findOrFail(base64_decode($id));
        $this->loadRows();
    }

    private function loadRows(): void
    {
        $this->recipeRows = $this->variantOption->ingredients->map(fn($ing) => [
            'ingredient_id' => $ing->id,
            'nama_bahan'    => $ing->nama_bahan,
            'satuan'        => $ing->satuan?->nama_satuan ?? '',
            'qty'           => $ing->pivot->qty,
        ])->toArray();
    }

    public function addIngredient(): void
    {
        $this->validate([
            'newIngredientId' => 'required|exists:ingredients,id',
            'newQty'          => 'required|numeric|min:0.01',
        ], [
            'newIngredientId.required' => 'Pilih bahan terlebih dahulu.',
            'newQty.required'          => 'Qty tidak boleh kosong.',
            'newQty.min'               => 'Qty harus lebih dari 0.',
        ]);

        // Cegah duplikat
        $exists = collect($this->recipeRows)->contains('ingredient_id', (int) $this->newIngredientId);
        if ($exists) {
            $this->addError('newIngredientId', 'Bahan ini sudah ada di resep.');
            return;
        }

        // Sync ke DB
        $this->variantOption->ingredients()->attach($this->newIngredientId, ['qty' => $this->newQty]);

        $this->newIngredientId = '';
        $this->newQty = '';
        $this->resetValidation();

        // Reload
        $this->variantOption->load('ingredients');
        $this->loadRows();

        $this->dispatch('showToast', message: 'Bahan berhasil ditambahkan ke resep.', type: 'success');
    }

    public function updateQty(int $ingredientId, float $qty): void
    {
        if ($qty <= 0) {
            $this->removeIngredient($ingredientId);
            return;
        }

        $this->variantOption->ingredients()->updateExistingPivot($ingredientId, ['qty' => $qty]);

        $this->variantOption->load('ingredients');
        $this->loadRows();

        $this->dispatch('showToast', message: 'Qty berhasil diperbarui.', type: 'success');
    }

    public function removeIngredient(int $ingredientId): void
    {
        $this->variantOption->ingredients()->detach($ingredientId);

        $this->variantOption->load('ingredients');
        $this->loadRows();

        $this->dispatch('showToast', message: 'Bahan dihapus dari resep.', type: 'success');
    }

    public function render()
    {
        $allIngredients = Ingredients::withoutGlobalScope('branch_filter')
            ->orderBy('nama_bahan')
            ->get();

        $title = 'Resep Bahan: ' . $this->variantOption->nama_opsi;

        return view('livewire.variant.manage-variant-ingredient', [
            'allIngredients' => $allIngredients,
            'title'          => $title,
            'backUrl'        => '/variant-group',
        ])->layout('layouts.app', ['title' => $title]);
    }
}
