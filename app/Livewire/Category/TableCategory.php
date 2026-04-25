<?php

namespace App\Livewire\Category;

use Livewire\Component;
use App\Models\Category;
use Livewire\WithPagination;

class TableCategory extends Component
{
    use WithPagination;
    public $search = '';
    public $perPage = 5; // default

    protected $paginationTheme = 'tailwind';

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function deleteCategory($id)
    {
        $category = Category::where('id', base64_decode($id))->first();
        if ($category) {
            $category->delete();
            $this->dispatch('showToast', message: 'Category Berhasil Dihapus.', type: 'success', title: 'Success');
        }
    }
    
    public function render()
    {
        $category = Category::query()
            ->when(!empty($this->search), function ($query) {
                $query->where('nama', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
            
        return view('livewire.category.table-category', [
            'category' => $category,
            'title' => 'Manajemen Kategori Menu'
        ])->layout('layouts.app', ['title' => 'Kategori']);
    }
}
