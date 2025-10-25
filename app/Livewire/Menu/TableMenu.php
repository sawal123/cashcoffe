<?php

namespace App\Livewire\Menu;

use App\Models\Menu;
use Livewire\Component;
use App\Models\Category;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class TableMenu extends Component
{
    use WithPagination;
    public $search = '';
    public $perPage = 20; // default
    protected $paginationTheme = 'tailwind';
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deletemenu($id)
    {
        $menu = Menu::where('id', base64_decode($id))->first();
        if ($menu) {
            if ($menu->gambar) {
                Storage::disk('public')->delete($menu->gambar);
            }
            $menu->delete();
            $this->dispatch('showToast', message: 'Menu Berhasil Dihapus.', type: 'success', title: 'Success');
        } else {
            $this->dispatch('showToast', message: 'Menu tidak ditemukan.', type: 'error', title: 'Error');
        }
    }
    public function render()
    {
        $menu = Menu::query()
            ->when(!empty($this->search), function ($query) {
                $query->where('nama_menu', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.menu.table-menu', ['menu' => $menu]);
    }
}
