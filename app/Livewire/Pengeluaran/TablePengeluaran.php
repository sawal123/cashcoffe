<?php

namespace App\Livewire\Pengeluaran;

use Livewire\Component;
use App\Models\Pengeluaran;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class TablePengeluaran extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    protected $paginationTheme = 'tailwind';

    public function updatingSearch()
    {
        // Reset ke halaman 1 setiap kali search berubah
        $this->resetPage();
    }

    public function getPengeluaransProperty()
    {
        return Pengeluaran::query()
            ->when($this->search, function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('kategori', 'like', "%{$this->search}%");
            })
            ->orderBy('tanggal_pengeluaran', 'desc')
            ->paginate($this->perPage);
    }

    public function deletePengeluaran($encodedId)
    {
        $id = base64_decode($encodedId);
        $pengeluaran = Pengeluaran::find($id);

        if (!$pengeluaran) {
            $this->dispatchBrowserEvent('toast', [
                'type' => 'error',
                'message' => 'Data tidak ditemukan.'
            ]);
            return;
        }

        if ($pengeluaran->bukti) {
            Storage::disk('public')->delete($pengeluaran->bukti);
        }

        $pengeluaran->delete();

        $this->dispatch('showToast', type: 'success', message: 'Data pengeluaran berhasil dihapus');
    }

    public function render()
    {
        return view('livewire.pengeluaran.table-pengeluaran', [
            'pengeluarans' => $this->pengeluarans
        ]);
    }
}
