<?php

namespace App\Livewire\Omset;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TableOmset extends Component
{
    public $dateFrom = '';

    public $dateTo = '';

    public $dateRange = '';

    public $merged = [];

    public $dataOmset = [];

    public $dataKomplemen = [];

    public $dataPengeluaran = [];

    public $dataQty;

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->syncDateRangeLabel();
        $this->hitungOmsetPeriode();
    }

    public function updatedDateFrom()
    {
        $this->normalizeDateRange();
        $this->hitungOmsetPeriode();
    }

    public function updatedDateTo()
    {
        $this->normalizeDateRange();
        $this->hitungOmsetPeriode();
    }

    public function setDateRange(?string $from = '', ?string $to = '', ?string $label = ''): void
    {
        $this->dateFrom = $from ?: '';
        $this->dateTo = $to ?: $this->dateFrom;
        $this->dateRange = $label ?: '';
        $this->normalizeDateRange();
        $this->hitungOmsetPeriode();
    }

    public function resetDateRange(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->dateRange = '';
        $this->hitungOmsetPeriode();
        $this->dispatch('omset-date-range-reset');
    }

    public function hitungOmsetPeriode()
    {
        $user = auth()->user();
        $branchId = ($user && $user->branch_id && ! $user->hasRole('superadmin')) ? $user->branch_id : null;

        $this->dataOmset = DB::table('pesanans')
            ->leftJoin('payment_methods', 'pesanans.payment_method_id', '=', 'payment_methods.id')
            ->select(
                DB::raw('DATE(pesanans.created_at) as tanggal'),
                DB::raw('COUNT(pesanans.id) as jumlah_pesanan'),
                DB::raw('SUM(pesanans.total) as total_omset'),
                DB::raw('SUM(pesanans.total_profit) as total_profit')
            )
            ->whereNull('pesanans.deleted_at')
            ->where('pesanans.status', 'selesai')
            ->tap(fn ($q) => $this->applyDateRange($q, 'pesanans.created_at'))
            ->where(function ($q) {
                $q->where('payment_methods.kode_metode', '!=', 'komplemen')
                    ->orWhereNull('pesanans.payment_method_id');
            })
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('pesanans.branch_id', $branchId);
            })
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')
            ->get();

        $this->dataKomplemen = DB::table('pesanans')
            ->join('payment_methods', 'pesanans.payment_method_id', '=', 'payment_methods.id')
            ->select(
                DB::raw('DATE(pesanans.created_at) as tanggal'),
                DB::raw('COUNT(pesanans.id) as jumlah_komplemen'),
                DB::raw('SUM(pesanans.total) as total_komplemen'),
                DB::raw('SUM(pesanans.total_profit) as total_profit_komplemen')
            )
            ->whereNull('pesanans.deleted_at')
            ->where('pesanans.status', 'selesai')
            ->tap(fn ($q) => $this->applyDateRange($q, 'pesanans.created_at'))
            ->where('payment_methods.kode_metode', '=', 'komplemen')
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('pesanans.branch_id', $branchId);
            })
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')
            ->get();

        $this->dataQty = DB::table('pesanan_items')
            ->join('pesanans', 'pesanans.id', '=', 'pesanan_items.pesanans_id')
            ->select(
                DB::raw('DATE(pesanans.created_at) as tanggal'),
                DB::raw('SUM(pesanan_items.qty) as jumlah_menu')
            )
            ->whereNull('pesanans.deleted_at')
            ->where('pesanans.status', 'selesai')
            ->tap(fn ($q) => $this->applyDateRange($q, 'pesanans.created_at'))
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('pesanans.branch_id', $branchId);
            })
            ->groupBy('tanggal')
            ->get();

        $this->dataPengeluaran = DB::table('pengeluarans')
            ->select(
                DB::raw('DATE(tanggal_pengeluaran) as tanggal'),
                DB::raw('SUM(total) as total_pengeluaran')
            )
            ->whereNull('deleted_at')
            ->tap(fn ($q) => $this->applyDateRange($q, 'tanggal_pengeluaran'))
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->groupBy('tanggal')
            ->get();

        $allDates = collect()
            ->merge($this->dataOmset->pluck('tanggal'))
            ->merge($this->dataKomplemen->pluck('tanggal'))
            ->merge($this->dataPengeluaran->pluck('tanggal'))
            ->unique()
            ->sortDesc();

        $this->merged = $allDates->map(function ($tanggal) {
            $omset = $this->dataOmset->firstWhere('tanggal', $tanggal);
            $qty = $this->dataQty->firstWhere('tanggal', $tanggal);
            $komplemen = $this->dataKomplemen->firstWhere('tanggal', $tanggal);
            $pengeluaran = $this->dataPengeluaran->firstWhere('tanggal', $tanggal);

            $totalOmset = $omset->total_omset ?? 0;
            $totalProfit = $omset->total_profit ?? 0;
            $totalPengeluaran = $pengeluaran->total_pengeluaran ?? 0;

            return (object) [
                'tanggal' => $tanggal,
                'jumlah_pesanan' => $omset ? ($omset->jumlah_pesanan ?? 0) : 0,
                'jumlah_menu' => $qty ? ($qty->jumlah_menu ?? 0) : 0,
                'total_komplemen' => $komplemen ? ($komplemen->total_komplemen ?? 0) : 0,
                'jumlah_komplemen' => $komplemen ? ($komplemen->jumlah_komplemen ?? 0) : 0,
                'total_omset' => $totalOmset,
                'total_profit' => $totalProfit,
                'total_pengeluaran' => $totalPengeluaran,
                'net_profit' => $totalProfit - $totalPengeluaran,
            ];
        });

        $this->dataOmset = $this->merged;
    }

    public function hitungOmsetBulanan()
    {
        $this->hitungOmsetPeriode();
    }

    private function applyDateRange($query, string $column): void
    {
        $from = $this->validDate($this->dateFrom);
        $to = $this->validDate($this->dateTo);

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from) {
            $query->whereDate($column, '>=', $from->toDateString());
        }

        if ($to) {
            $query->whereDate($column, '<=', $to->toDateString());
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
        $totalOmset = $this->dataOmset->sum('total_omset');
        $totalProfit = $this->dataOmset->sum('total_profit');
        $totalKomplemen = $this->dataOmset->sum('total_komplemen');
        $totalPengeluaran = $this->dataOmset->sum('total_pengeluaran');
        $netProfit = $totalProfit - $totalPengeluaran;

        return view('livewire.omset.table-omset', [
            'dataOmset' => $this->dataOmset,
            'totalOmset' => $totalOmset,
            'totalProfit' => $totalProfit,
            'totalKomplemen' => $totalKomplemen,
            'totalPengeluaran' => $totalPengeluaran,
            'netProfit' => $netProfit,
            'title' => 'Laporan Omset & Keuntungan',
        ])->layout('layouts.app', ['title' => 'Laporan Omset']);
    }
}
