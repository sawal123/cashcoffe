<?php

namespace App\Livewire\Variant;

use App\Models\Menu;
use App\Models\VariantGroup;
use Livewire\Component;

class ManageMenuVariant extends Component
{
    public $menu;

    public $selectedGroups = [];
    public $originalSelectedGroups = [];
    public $isDirty = false;

    /**
     * Computed property to determine if selections changed from original.
     */
    public function getIsDirtyProperty()
    {
        return (count(array_diff($this->selectedGroups, $this->originalSelectedGroups)) > 0)
            || (count(array_diff($this->originalSelectedGroups, $this->selectedGroups)) > 0);
    }

    public function mount($id)
    {
        $this->menu = Menu::with('variantGroups')->findOrFail($id);
        $this->selectedGroups = $this->menu->variantGroups->pluck('id')->toArray();
        $this->originalSelectedGroups = $this->selectedGroups;
        $this->isDirty = (count(array_diff($this->selectedGroups, $this->originalSelectedGroups)) > 0)
            || (count(array_diff($this->originalSelectedGroups, $this->selectedGroups)) > 0);
    }

    public function save()
    {
        $this->menu->variantGroups()->sync($this->selectedGroups);
        $this->originalSelectedGroups = $this->selectedGroups;
        $this->isDirty = false;
        $this->dispatch('showToast', message: 'Pemetaan varian berhasil diperbarui', type: 'success');
    }

    /**
     * Livewire hook when selectedGroups is updated from the frontend.
     */
    public function updatedSelectedGroups()
    {
        $this->isDirty = (count(array_diff($this->selectedGroups, $this->originalSelectedGroups)) > 0)
            || (count(array_diff($this->originalSelectedGroups, $this->selectedGroups)) > 0);
    }

    public function render()
    {
        $title = 'Varian Menu: ' . $this->menu->nama_menu;
        return view('livewire.variant.manage-menu-variant', [
            'allGroups' => VariantGroup::latest()->get(),
            'title' => $title,
            'backUrl' => '/menu'
        ])->layout('layouts.app', ['title' => $title]);
    }
}
