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
    public $filterMonth = '';
    public $filterYear = '';

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->filterMonth = ''; // Initial empty for Dec/all
        $this->filterYear = date('Y');
    }

    public function updated($property)
    {
        if (in_array($property, ['search', 'filterMonth', 'filterYear', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function getPengeluaransProperty()
    {
        return Pengeluaran::query()
            ->when($this->search, function ($q) {
                $q->where(function($sub) {
                    $sub->where('title', 'like', "%{$this->search}%")
                        ->orWhere('kategori', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterMonth, function ($q) {
                $q->whereMonth('tanggal_pengeluaran', $this->filterMonth);
            })
            ->when($this->filterYear, function ($q) {
                $q->whereYear('tanggal_pengeluaran', $this->filterYear);
            })
            ->orderBy('tanggal_pengeluaran', 'desc')
            ->paginate($this->perPage);
    }

    public function getTotalAllTimeProperty()
    {
        return Pengeluaran::sum('total');
    }

    public function getTotalFilteredProperty()
    {
        return Pengeluaran::query()
            ->when($this->search, function ($q) {
                $q->where(function($sub) {
                    $sub->where('title', 'like', "%{$this->search}%")
                        ->orWhere('kategori', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterMonth, function ($q) {
                $q->whereMonth('tanggal_pengeluaran', $this->filterMonth);
            })
            ->when($this->filterYear, function ($q) {
                $q->whereYear('tanggal_pengeluaran', $this->filterYear);
            })
            ->sum('total');
    }

    public function getGrowthPercentageProperty()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $lastMonth = now()->subMonth();
        $lastMonthNum = $lastMonth->month;
        $lastMonthYear = $lastMonth->year;

        $totalThisMonth = Pengeluaran::whereMonth('tanggal_pengeluaran', $currentMonth)
            ->whereYear('tanggal_pengeluaran', $currentYear)
            ->sum('total');

        $totalLastMonth = Pengeluaran::whereMonth('tanggal_pengeluaran', $lastMonthNum)
            ->whereYear('tanggal_pengeluaran', $lastMonthYear)
            ->sum('total');

        if ($totalLastMonth == 0) {
            return $totalThisMonth > 0 ? 100 : 0;
        }

        return (($totalThisMonth - $totalLastMonth) / $totalLastMonth) * 100;
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
