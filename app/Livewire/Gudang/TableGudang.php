<?php

namespace App\Livewire\Gudang;

use App\Models\Gudang;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class TableGudang extends Component
{

    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $gudangId = null;

    protected $listeners = ['open-modal' => 'confirmDelete'];

    // Reset pagination saat search berubah
    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function mount()
    {
        $this->search = '';
        $this->perPage = 10;
    }

    public function deleteGudang($id)
    {
        DB::beginTransaction();
        try {
            $gudang = Gudang::find(base64_decode($id));

            if (!$gudang) {
                $this->dispatch('showToast', [
                    'type' => 'error',
                    'message' => 'Data bahan tidak ditemukan.'
                ]);
                return;
            }

            // Hapus riwayat terkait (jika ada relasi)
            $gudang->riwayats()->delete();

            // Hapus data gudang
            $gudang->delete();

            DB::commit();
            $this->dispatch('showToast', type: 'success', message: 'Data bahan berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showToast', type: 'error', message: 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
    public function render()
    {
        $gudangs = Gudang::query()
            ->when($this->search, function ($query) {
                $query->where('nama_bahan', 'like', '%' . $this->search . '%');
            })
            ->orderBy('nama_bahan', 'asc')
            ->paginate($this->perPage);
        return view('livewire.gudang.table-gudang', [
            'gudangs' => $gudangs
        ]);
    }
}
