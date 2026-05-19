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
            ->where('pesanans.status', 'selesai')
            ->whereMonth('pesanans.created_at', $this->bulan)
            ->whereYear('pesanans.created_at', $this->tahun)
            ->where('payment_methods.kode_metode', '=', 'komplemen')
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
            ->where('pesanans.status', 'selesai')
            ->whereMonth('pesanans.created_at', $this->bulan)
            ->whereYear('pesanans.created_at', $this->tahun)
            ->groupBy('tanggal')
            ->get();

        // 🔹 Gabungkan hasil berdasarkan tanggal
        $this->merged = $this->dataOmset->map(function ($item) {
            // jumlah menu
            $qty = $this->dataQty->firstWhere('tanggal', $item->tanggal);
            $item->jumlah_menu = $qty->jumlah_menu ?? 0;

            // total komplemen
            $komplemen = $this->dataKomplemen->firstWhere('tanggal', $item->tanggal);
            $item->total_komplemen = $komplemen->total_komplemen ?? 0;
            $item->jumlah_komplemen = $komplemen->jumlah_komplemen ?? 0;

            return $item;
        });
    }



    public function render()
    {
        $totalOmset = $this->dataOmset->sum('total_omset');
        $totalProfit = $this->dataOmset->sum('total_profit');

        // Tambahan total untuk pembayaran "komplemen"
        $totalKomplemen = $this->dataKomplemen->sum('total_komplemen');
        $totalProfitKomplemen = $this->dataKomplemen->sum('total_profit_komplemen');

        return view('livewire.omset.table-omset', [
            'dataOmset' => $this->merged, // hasil gabungan normal
            'dataKomplemen' => $this->dataKomplemen, // hasil khusus komplemen
            'totalOmset' => $totalOmset,
            'totalProfit' => $totalProfit,
            'totalKomplemen' => $totalKomplemen,
            'totalProfitKomplemen' => $totalProfitKomplemen,
            'title' => 'Laporan Omset & Keuntungan'
        ])->layout('layouts.app', ['title' => 'Laporan Omset']);
    }
}
