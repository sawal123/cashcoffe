<?php

namespace App\Livewire\Pengeluaran;

use App\Models\Pengeluaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class TablePengeluaran extends Component
{
    use WithPagination;

    public $search = '';

    public $perPage = 10;

    public $dateFrom = '';

    public $dateTo = '';

    public $dateRange = '';

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->syncDateRangeLabel();
    }

    public function updated($property)
    {
        if (in_array($property, ['search', 'dateFrom', 'dateTo', 'dateRange', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function updatedDateFrom(): void
    {
        $this->normalizeDateRange();
    }

    public function updatedDateTo(): void
    {
        $this->normalizeDateRange();
    }

    public function resetDateRange(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->dateRange = '';
        $this->resetPage();
        $this->dispatch('pengeluaran-date-range-reset');
    }

    public function setDateRange(?string $from = '', ?string $to = '', ?string $label = ''): void
    {
        $this->dateFrom = $from ?: '';
        $this->dateTo = $to ?: $this->dateFrom;
        $this->dateRange = $label ?: '';
        $this->normalizeDateRange();
        $this->resetPage();
    }

    #[Computed]
    public function pengeluarans()
    {
        return Pengeluaran::with(['branch', 'satuanBahan'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'like', "%{$this->search}%")
                        ->orWhere('kategori', 'like', "%{$this->search}%");
                });
            })
            ->tap(fn ($q) => $this->applyDateRange($q))
            ->orderBy('tanggal_pengeluaran', 'desc')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function totalAllTime()
    {
        return Pengeluaran::query()
            ->sum('total');
    }

    #[Computed]
    public function totalFiltered()
    {
        return Pengeluaran::query()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'like', "%{$this->search}%")
                        ->orWhere('kategori', 'like', "%{$this->search}%");
                });
            })
            ->tap(fn ($q) => $this->applyDateRange($q))
            ->sum('total');
    }

    #[Computed]
    public function growthPercentage()
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

        if (! $pengeluaran) {
            $this->dispatchBrowserEvent('toast', [
                'type' => 'error',
                'message' => 'Data tidak ditemukan.',
            ]);

            return;
        }

        if ($pengeluaran->bukti) {
            Storage::disk('public')->delete($pengeluaran->bukti);
        }

        $pengeluaran->delete();

        $this->dispatch('showToast', type: 'success', message: 'Data pengeluaran berhasil dihapus');
    }

    private function applyDateRange($query): void
    {
        $from = $this->validDate($this->dateFrom);
        $to = $this->validDate($this->dateTo);

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from) {
            $query->whereDate('tanggal_pengeluaran', '>=', $from->toDateString());
        }

        if ($to) {
            $query->whereDate('tanggal_pengeluaran', '<=', $to->toDateString());
        }
    }

    private function normalizeDateRange(): void
    {
        $from = $this->validDate($this->dateFrom);
        $to = $this->validDate($this->dateTo);

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $this->dateFrom = $from?->toDateString() ?? '';
        $this->dateTo = $to?->toDateString() ?? '';
        $this->syncDateRangeLabel();
    }

    private function syncDateRangeLabel(): void
    {
        if ($this->dateRange && str_contains($this->dateRange, ' to ')) {
            return;
        }

        if ($this->dateFrom && $this->dateTo) {
            $this->dateRange = $this->dateFrom.' to '.$this->dateTo;
        } elseif ($this->dateFrom) {
            $this->dateRange = $this->dateFrom;
        } else {
            $this->dateRange = '';
        }
    }

    private function validDate(?string $date): ?Carbon
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($date))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.pengeluaran.table-pengeluaran', [
            'pengeluarans' => $this->pengeluarans,
            'title' => 'Manajemen Pengeluaran',
        ])->layout('layouts.app', ['title' => 'Pengeluaran']);
    }
}
