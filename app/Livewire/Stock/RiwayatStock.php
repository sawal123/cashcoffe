<?php

namespace App\Livewire\Stock;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\RiwayatStock as ModelsRiwayatStock;

class RiwayatStock extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $search = '';
    public $filterType = 'semua';
    public $selectedId;

    protected $queryString = ['search', 'filterType', 'perPage',  'tanggal' => ['except' => ''],];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function setFilter($type)
    {
        $this->filterType = $type;
    }

    public function deleteRiwayat($id)
    {
        $id = base64_decode($id);

        DB::transaction(function () use ($id) {

            $riwayat = ModelsRiwayatStock::with('ingredient')->findOrFail($id);

            // Jika tipe in → berarti sebelumnya stok bertambah → hapus = kurangi stok
            if ($riwayat->tipe === 'in') {
                $riwayat->ingredient->decrement('stok', $riwayat->qty);
            }

            // Jika tipe out → berarti stok sebelumnya berkurang → hapus = tambahkan stok
            if ($riwayat->tipe === 'out') {
                $riwayat->ingredient->increment('stok', $riwayat->qty);
            }

            $riwayat->delete();
        });

        $this->dispatch('showToast', type: 'success', message: 'Riwayat berhasil dihapus');
    }
    public $tanggal;
   
    public function mount()
    {
        $this->tanggal = request()->query('tanggal')
            ?? Carbon::today()->toDateString();
    }
    public function updatedTanggal()
    {
        $this->resetPage();
    }
    public function render()
    {
        $riwayats = ModelsRiwayatStock::with('ingredient')
            ->when($this->search, function ($q) {
                $q->whereHas('ingredient', function ($s) {
                    $s->where('nama_bahan', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterType !== 'semua', function ($q) {
                $q->where('tipe', $this->filterType);
            })
            ->when($this->tanggal, function ($q) {
                $q->whereDate('created_at', $this->tanggal);
            })
            ->latest()
            ->paginate($this->perPage);


        return view('livewire.stock.riwayat-stock', [
            'riwayats' => $riwayats,
        ]);
    }
}
