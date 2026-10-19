<?php

namespace App\Livewire\RiwayatGudang;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\GudangRiwayat;
use Illuminate\Support\Facades\DB;

class TableRiwayat extends Component
{
    use WithPagination;

    public function mount()
    {
        $this->authorizeBranch();
    }

    public $search = '';
    public $filterType = 'semua'; // semua | masuk | keluar
    public $perPage = 10;
    public $riwayatId;

    protected $paginationTheme = 'tailwind';
    protected $listeners = ['confirmDelete'];



    private function authorizeBranch(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->hasAnyRole(['admin', 'superadmin']), 403);
        abort_if(! $user->hasRole('superadmin') && $user->branch_id === null, 403);
    }

    public function setFilter($type)
    {
        $this->filterType = $type;
        $this->resetPage();
    }

    public function confirmDelete($data)
    {
        $this->riwayatId = base64_decode($data['id']);
    }

    public function deleteRiwayat($id)
    {
        $this->authorizeBranch();
        DB::beginTransaction();
        try {
            $riwayat = GudangRiwayat::visibleTo(auth()->user())->find(base64_decode($id, true));

            if (! $riwayat) {
                DB::rollBack();
                $this->dispatch('showToast', [
                    'type' => 'error',
                    'message' => 'Data riwayat tidak ditemukan.'
                ]);
                return;
            }

            $riwayat->delete();
            DB::commit();

            $this->dispatch('showToast', type: 'success', message: 'Data Riwayat Berhasil Dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showToast', type: 'success', message: 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
    public function render()
    {
        $this->authorizeBranch();
        $query = GudangRiwayat::visibleTo(auth()->user())
            ->with('gudang.branch')
            ->when($this->filterType !== 'semua', function ($q) {
                $q->where('tipe', $this->filterType);
            })
            ->when($this->search, function ($q) {
                $q->whereHas('gudang', function ($q2) {
                    $q2->where('nama_bahan', 'like', '%' . $this->search . '%');
                });
            })
            ->latest();

        $riwayats = $query->paginate($this->perPage);
        return view('livewire.riwayat-gudang.table-riwayat', [
            'riwayats' => $riwayats,
        ])->layout('layouts.app', ['title' => 'Riwayat Gudang']);
    }
}
