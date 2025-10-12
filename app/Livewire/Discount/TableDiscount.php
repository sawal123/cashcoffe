<?php

namespace App\Livewire\Discount;

use Livewire\Component;
use App\Models\Discount;
use Livewire\WithPagination;

class TableDiscount extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $discountId = null;

    protected $paginationTheme = 'tailwind';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        
        $diskon = Discount::findOrFail(base64_decode($id));
        $diskon->delete();

        $this->dispatch('showToast', message: 'Discount Berhasil Dihapus', type: 'success', title: 'Success');
    }

    public function render()
    {
        $discounts = Discount::query()
            ->when($this->search, function ($query) {
                $query->where('nama_diskon', 'like', '%' . $this->search . '%')
                    ->orWhere('jenis_diskon', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.discount.table-discount', ['discounts' => $discounts]);
    }
}
