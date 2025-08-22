<?php

namespace App\Livewire\Category;

use App\Models\Category;
use Livewire\Component;

class CategoryCreate extends Component
{
    public $category;
    public $categoryId;
    public $is_active;

    public function fieldReset()
    {
        $this->category = '';
    }
    public function mount($categoryId = null)
    {
        $this->categoryId = $categoryId;
        // dd($categoryId);
        if ($categoryId) {
            $category = Category::find($categoryId);
            if ($category) {
                $this->category = $category->nama;
                $this->is_active = $category->is_active;
            }
        }
        // dd($this->is_active);
    }

    public function update($field)
    {

        $category = Category::find($this->categoryId);
        if ($category) {
            $category->update([
                'nama' => $this->category,
                'is_active' => $this->is_active,
            ]);
        }
         $this->dispatch('showToast', message: 'Category Berhasil Diupdate', type: 'success', title: 'Success');
    }
    public function simpan()
    {
        // dd($this->category);
        $lastCategory = Category::orderBy('urutan', 'desc')->first();

        // dd($lastCategory);
        $category = Category::create([
            'nama' => $this->category,
            'urutan' => ($lastCategory?->urutan ?? 0) + 1,
            'is_active' => true
        ]);
        $this->fieldReset();
        $this->dispatch('showToast', message: 'Category Berhasil Disimpan', type: 'success', title: 'Success');
    }
    public function render()
    {
        
        return view('livewire.category.category-create');
    }
}
