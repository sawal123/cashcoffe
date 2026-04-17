<?php

namespace App\Livewire\Branch;

use Livewire\Component;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class MenuAvailability extends Component
{
    public $search = '';
    public $selectedBranchId;

    public function mount()
    {
        $user = auth()->user();
        if ($user->hasRole('superadmin')) {
            $this->selectedBranchId = Branch::first()?->id;
        } else {
            $this->selectedBranchId = $user->branch_id;
        }
    }

    public function toggleAvailability($menuId)
    {
        $branch = Branch::find($this->selectedBranchId);
        if (!$branch) return;

        $exists = DB::table('branch_menu')
            ->where('branch_id', $this->selectedBranchId)
            ->where('menu_id', $menuId)
            ->first();

        if ($exists) {
            DB::table('branch_menu')
                ->where('id', $exists->id)
                ->update([
                    'is_available' => !$exists->is_available,
                    'updated_at' => now()
                ]);
        } else {
            DB::table('branch_menu')->insert([
                'branch_id' => $this->selectedBranchId,
                'menu_id' => $menuId,
                'is_available' => false, // Karena defaultnya true di query pengambilan data, toggle pertama kali berarti mematikan menu
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->dispatch('showToast', type: 'success', message: 'Ketersediaan menu diperbarui.');
    }

    public function render()
    {
        $branch = Branch::find($this->selectedBranchId);
        $priceTierId = $branch ? $branch->price_tier_id : null;

        $categories = Category::with([
            'menus' => function ($query) use ($priceTierId) {
                $query->where('is_active', true)
                    ->where('nama_menu', 'like', '%' . $this->search . '%')
                    // Syarat Pusat: Hanya yang sudah ada harga di Tier ini
                    ->whereHas('menuPrices', function ($q) use ($priceTierId) {
                        $q->where('price_tier_id', $priceTierId);
                    })
                    // Ambil status pivot jika ada
                    ->leftJoin('branch_menu', function($join) {
                        $join->on('menus.id', '=', 'branch_menu.menu_id')
                             ->where('branch_menu.branch_id', '=', $this->selectedBranchId);
                    })
                    ->select('menus.*', DB::raw('IFNULL(branch_menu.is_available, 1) as branch_available'));
            }
        ])->get();

        return view('livewire.branch.menu-availability', [
            'categories' => $categories,
            'branches' => auth()->user()->hasRole('superadmin') ? Branch::all() : []
        ]);
    }
}
