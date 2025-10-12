<?php

namespace App\Livewire\Menu;

use App\Models\Menu;
use Livewire\Component;
use App\Models\Category;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;
    public $nama_menu, $categories_id, $harga, $is_active, $deskripsi, $gambar;
    public $h_pokok, $h_promo = 0;
    public $menuId = null, $gambarUrl = null;
    public function simpan()
    {
        $this->validate([
            'nama_menu' => 'string|max:255',
            'categories_id' => 'exists:categories,id',
            'harga' => 'numeric|min:0',
            'is_active' => 'boolean',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|max:1024',
        ]);
        // dd($this->gambar);
        try {
            $gambarPath = null;
            if ($this->gambar) {
                $gambarPath = $this->gambar->store('menu', 'public');
            }

            Menu::create([
                'nama_menu'    => $this->nama_menu,
                'categories_id' => $this->categories_id,
                'h_pokok'        => $this->h_pokok,
                'harga'        => $this->harga,
                'h_promo'        => $this->h_promo,
                'is_active'    => $this->is_active ?? false,
                'deskripsi'    => $this->deskripsi,
                'gambar'       => $gambarPath,
            ]);
            $this->dispatch('reset-upload');
            $this->dispatch('showToast', message: 'Menu Berhasil Dibuat', type: 'success', title: 'Success');
            $this->resetForm();
        } catch (\Exception $e) {
            Log::error('Gagal simpan menu: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menyimpan menu.');
        }
    }

    public function update()
    {
        $this->validate([
            'nama_menu' => 'string|max:255',
            'categories_id' => 'exists:categories,id',
            'harga' => 'numeric|min:0',
            'is_active' => 'boolean',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|max:1024',
        ]);

        try {
            $menu = Menu::find($this->menuId);
            if (!$menu) {
                session()->flash('error', 'Menu not found.');
                return;
            }

            if ($this->gambar) {
                // Optionally delete the old image here if needed
                $gambarPath = $this->gambar->store('menu', 'public');
                $menu->gambar = $gambarPath;
            }

            $menu->update([
                'nama_menu'    => $this->nama_menu,
                'categories_id' => $this->categories_id,
                'h_pokok'        => $this->h_pokok,
                'harga'        => $this->harga,
                'h_promo'        => $this->h_promo,
                'is_active'    => $this->is_active ?? false,
                'deskripsi'    => $this->deskripsi,
            ]);
            // $this->dispatch('reset-upload');
            $this->dispatch('showToast', message: 'Menu Berhasil Diupdate', type: 'success', title: 'Success');
        } catch (\Exception $e) {
            Log::error('Gagal update menu: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat mengupdate menu.');
        }
    }
    public $hidden = 'hidden';
    public function mount($menuId = null)
    {
        // dd($menuId);
       $this->hidden =  !$this->gambarUrl ? 'hidden' : '';
        if ($menuId) {
            $menu = Menu::find(base64_decode($menuId));
            if ($menu) {
                $this->menuId = $menu->id;
                $this->nama_menu = $menu->nama_menu;
                $this->categories_id = $menu->categories_id;
                $this->h_pokok = $menu->h_pokok;
                $this->harga = $menu->harga;
                $this->h_promo = $menu->h_promo;
                $this->is_active = $menu->is_active;
                $this->deskripsi = $menu->deskripsi;
                // Note: Gambar is not set here as it's a file upload
                $this->gambarUrl = $menu->gambar ? asset('storage/' . $menu->gambar) : asset('assets/images/user.png');
            } else {
                session()->flash('error', 'Menu not found.');
            }
        }
    }
    public function resetForm()
    {
        $this->nama_menu = '';
        $this->categories_id = '';
        $this->harga = '';
        $this->is_active = false;
        $this->deskripsi = '';
        $this->gambar = '';
    }
    public function render()
    {
        $category = Category::where('is_active', 1)->get();
        return view('livewire.menu.create', ['category' => $category]);
    }
}
