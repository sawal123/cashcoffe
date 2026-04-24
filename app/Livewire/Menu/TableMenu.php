<?php

namespace App\Livewire\Menu;

use App\Models\Menu;
use Livewire\Component;
use App\Models\Category;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class TableMenu extends Component
{
    use WithPagination;

    #[On('refresh-menu')]
    public function refresh() {}

    public $search = '';
    public $perPage = 20; // default
    protected $paginationTheme = 'tailwind';
    public function updatingCategory()
    {
        $this->resetPage();
    }

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
    public $category = ''; // filter kategori

    public function render()
    {
        $user = auth()->user();
        $priceTierId = $user->branch ? $user->branch->price_tier_id : (\App\Models\PriceTier::first()?->id ?? 1);

        $menu = Menu::query()
            ->withSum(['pesananItems as jumlah_terjual' => function ($q) {
                $q->whereHas('pesanan', function ($p) {
                    $p->where('status', 'selesai');
                });
            }], 'qty')
            ->with(['menuPrices' => function($q) use ($priceTierId) {
                $q->where('price_tier_id', $priceTierId);
            }])
            ->when(!empty($this->category), function ($query) {
                $query->where('categories_id', $this->category);
            })
            ->when(!empty($this->search), function ($query) {
                $query->where('nama_menu', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.menu.table-menu', [
            'menu' => $menu,
            'categories' => Category::all()
        ]);
    }
}
