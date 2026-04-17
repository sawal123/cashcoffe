<?php

namespace App\Livewire\Menu;

use App\Models\Menu;
use Livewire\Component;
use App\Models\Category;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;
    public $nama_menu, $categories_id, $is_active = false, $deskripsi, $gambar;
    public $h_pokok;
    public $tieredPrices = []; // [tier_id => ['harga' => X, 'h_promo' => Y]]
    public $menuId = null, $gambarUrl = null;

    public function updatedGambar()
    {
        $this->validate([
            'gambar' => 'image|max:1024',
        ]);
        $this->gambarUrl = $this->gambar->temporaryUrl();
    }
    public function simpan()
    {
        $this->validate([
            'nama_menu' => 'string|max:255',
            'categories_id' => 'exists:categories,id',
            'is_active' => 'boolean',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|max:1024',
            'tieredPrices.*.harga' => 'required|numeric|min:0',
            'tieredPrices.*.h_promo' => 'nullable|numeric|min:0',
        ]);
        // dd($this->gambar);
        try {
            $gambarPath = null;
            if ($this->gambar) {
                $gambarPath = $this->gambar->store('menu', 'public');
            }

            $menu = Menu::create([
                'nama_menu'    => $this->nama_menu,
                'categories_id' => $this->categories_id,
                'h_pokok'        => $this->h_pokok,
                'is_active'    => $this->is_active ?? false,
                'deskripsi'    => $this->deskripsi,
                'gambar'       => $gambarPath,
            ]);

            // Simpan Harga Bertingkat
            foreach ($this->tieredPrices as $tierId => $priceData) {
                \App\Models\MenuPrice::create([
                    'menu_id' => $menu->id,
                    'price_tier_id' => $tierId,
                    'harga' => $priceData['harga'] ?? 0,
                    'h_promo' => $priceData['h_promo'] ?? 0,
                ]);
            }
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
        // Security Check
        if (!auth()->user()->hasRole('superadmin')) {
            abort(403, 'Aksi ini hanya diperbolehkan untuk Superadmin (Pusat).');
        }

        $this->validate([
            'nama_menu' => 'string|max:255',
            'categories_id' => 'exists:categories,id',
            'is_active' => 'boolean',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|max:1024',
            'tieredPrices.*.harga' => 'required|numeric|min:0',
            'tieredPrices.*.h_promo' => 'nullable|numeric|min:0',
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
                'is_active'    => $this->is_active ?? false,
                'deskripsi'    => $this->deskripsi,
            ]);

            // Sync Harga Bertingkat
            foreach ($this->tieredPrices as $tierId => $priceData) {
                \App\Models\MenuPrice::updateOrCreate(
                    ['menu_id' => $menu->id, 'price_tier_id' => $tierId],
                    [
                        'harga' => $priceData['harga'] ?? 0,
                        'h_promo' => $priceData['h_promo'] ?? 0,
                    ]
                );
            }
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
        $this->hidden = !$this->gambarUrl ? 'hidden' : '';
        $tiers = \App\Models\PriceTier::all();

        // Inisialisasi tieredPrices dengan tier yang ada
        foreach ($tiers as $tier) {
            $this->tieredPrices[$tier->id] = ['harga' => 0, 'h_promo' => 0];
        }

        if ($menuId) {
            $menu = Menu::find(base64_decode($menuId));
            if ($menu) {
                $this->menuId = $menu->id;
                $this->nama_menu = $menu->nama_menu;
                $this->categories_id = $menu->categories_id;
                $this->h_pokok = (int) $menu->h_pokok;
                $this->is_active = (bool) $menu->is_active;
                $this->deskripsi = $menu->deskripsi;

                // Load existing prices from menu_prices
                foreach ($this->tieredPrices as $tierId => $val) {
                    $mp = $menu->menuPrices()->where('price_tier_id', $tierId)->first();
                    if ($mp) {
                        $this->tieredPrices[$tierId] = [
                            'harga' => (int) $mp->harga,
                            'h_promo' => (int) $mp->h_promo,
                        ];
                    }
                }

                if ($menu->gambar) {
                    $this->gambarUrl = asset('storage/' . $menu->gambar);
                }
            } else {
                session()->flash('error', 'Menu not found.');
            }
        }
    }
    public function resetForm()
    {
        $this->nama_menu = '';
        $this->categories_id = '';
        $this->is_active = false;
        $this->deskripsi = '';
        $this->gambar = '';
        $this->h_pokok = '';
        
        // Reset tiered prices
        foreach ($this->tieredPrices as $tierId => $val) {
            $this->tieredPrices[$tierId] = ['harga' => 0, 'h_promo' => 0];
        }
    }
    public function render()
    {
        $category = Category::where('is_active', 1)->get();
        $tiers = \App\Models\PriceTier::all();
        return view('livewire.menu.create', [
            'category' => $category,
            'tiers' => $tiers
        ]);
    }
}
