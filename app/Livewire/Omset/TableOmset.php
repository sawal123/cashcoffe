<?php

namespace App\Livewire\Omset;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TableOmset extends Component
{
    public $bulan;

    public $tahun;
    public $merged = []; // ← tambahkan ini
    public $dataOmset = [];
    public $dataKomplemen = [];
    public $dataPengeluaran = [];
    public $dataQty;

    public function mount()
    {
        $this->bulan = date('m');
        $this->tahun = date('Y');
        $this->hitungOmsetBulanan();
    }

    public function updatedBulan()
    {
        $this->hitungOmsetBulanan();
    }

    public function updatedTahun()
    {
        $this->hitungOmsetBulanan();
    }

    public function hitungOmsetBulanan()
    {
        $user = auth()->user();
        $branchId = ($user && $user->branch_id && !$user->hasRole('superadmin')) ? $user->branch_id : null;

        // 🔹 Omset normal (bukan komplemen)
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
            ->whereMonth('pesanans.created_at', $this->bulan)
            ->whereYear('pesanans.created_at', $this->tahun)
            ->where(function($q) {
                $q->where('payment_methods.kode_metode', '!=', 'komplemen')
                  ->orWhereNull('pesanans.payment_method_id');
            })
            ->when($branchId, function($q) use ($branchId) {
                $q->where('pesanans.branch_id', $branchId);
            })
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')
            ->get();

        // 🔹 Data komplemen (metode pembayaran = 'komplemen')
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
            ->whereMonth('pesanans.created_at', $this->bulan)
            ->whereYear('pesanans.created_at', $this->tahun)
            ->where('payment_methods.kode_metode', '=', 'komplemen')
            ->when($branchId, function($q) use ($branchId) {
                $q->where('pesanans.branch_id', $branchId);
            })
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')
            ->get();

        // 🔹 Jumlah menu
        $this->dataQty = DB::table('pesanan_items')
            ->join('pesanans', 'pesanans.id', '=', 'pesanan_items.pesanans_id')
            ->select(
                DB::raw('DATE(pesanans.created_at) as tanggal'),
                DB::raw('SUM(pesanan_items.qty) as jumlah_menu')
            )
            ->whereNull('pesanans.deleted_at')
            ->where('pesanans.status', 'selesai')
            ->whereMonth('pesanans.created_at', $this->bulan)
            ->whereYear('pesanans.created_at', $this->tahun)
            ->when($branchId, function($q) use ($branchId) {
                $q->where('pesanans.branch_id', $branchId);
            })
            ->groupBy('tanggal')
            ->get();

        // 🔹 Data pengeluaran
        $this->dataPengeluaran = DB::table('pengeluarans')
            ->select(
                DB::raw('DATE(tanggal_pengeluaran) as tanggal'),
                DB::raw('SUM(total) as total_pengeluaran')
            )
            ->whereNull('deleted_at')
            ->whereMonth('tanggal_pengeluaran', $this->bulan)
            ->whereYear('tanggal_pengeluaran', $this->tahun)
            ->when($branchId, function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->groupBy('tanggal')
            ->get();

        // 🔹 Gabungkan hasil berdasarkan tanggal (termasuk tanggal yang hanya memiliki pengeluaran atau penjualan saja)
        $allDates = collect()
            ->merge($this->dataOmset->pluck('tanggal'))
            ->merge($this->dataPengeluaran->pluck('tanggal'))
            ->unique()
            ->sortDesc();

        $this->merged = $allDates->map(function ($tanggal) {
            $omset = $this->dataOmset->firstWhere('tanggal', $tanggal);
            $qty = $this->dataQty->firstWhere('tanggal', $tanggal);
            $komplemen = $this->dataKomplemen->firstWhere('tanggal', $tanggal);
            $pengeluaran = $this->dataPengeluaran->firstWhere('tanggal', $tanggal);

            $tOmset = $omset->total_omset ?? 0;
            $tProfit = $omset->total_profit ?? 0;
            $tPengeluaran = $pengeluaran->total_pengeluaran ?? 0;

            return (object) [
                'tanggal' => $tanggal,
                'jumlah_pesanan' => $omset ? ($omset->jumlah_pesanan ?? 0) : 0,
                'jumlah_menu' => $qty ? ($qty->jumlah_menu ?? 0) : 0,
                'total_komplemen' => $komplemen ? ($komplemen->total_komplemen ?? 0) : 0,
                'jumlah_komplemen' => $komplemen ? ($komplemen->jumlah_komplemen ?? 0) : 0,
                'total_omset' => $tOmset,
                'total_profit' => $tProfit,
                'total_pengeluaran' => $tPengeluaran,
                'net_profit' => $tProfit - $tPengeluaran,
            ];
        });

        // Timpa dataOmset agar berisi data hasil gabungan yang lengkap (termasuk properti jumlah_menu)
        $this->dataOmset = $this->merged;
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
            'title' => 'Laporan Omset & Keuntungan'
        ])->layout('layouts.app', ['title' => 'Laporan Omset']);
    }
}
