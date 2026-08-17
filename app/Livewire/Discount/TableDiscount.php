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

        // Branch isolation: non-superadmin hanya boleh hapus discount branch sendiri.
        // Shared (branch_id NULL) & branch lain ditolak 403.
        abort_unless($diskon->isManageableBy(auth()->user()), 403, 'Anda tidak memiliki akses untuk menghapus diskon ini.');

        $diskon->delete();

        $this->dispatch('showToast', message: 'Discount Berhasil Dihapus', type: 'success', title: 'Success');
    }

    public function render()
    {
        $discounts = Discount::query()
            ->manageableBy(auth()->user())
            ->when($this->search, function ($query) {
                // OR wajib digroup agar tidak menembus filter manageableBy
                $query->where(function ($q) {
                    $q->where('nama_diskon', 'like', '%' . $this->search . '%')
                        ->orWhere('jenis_diskon', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.discount.table-discount', [
            'discounts' => $discounts,
            'title' => 'Daftar Promo & Diskon'
        ])->layout('layouts.app', ['title' => 'Diskon']);
    }
}
