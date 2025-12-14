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
            ->select(
                DB::raw('DATE(created_at) as tanggal'),
                DB::raw('COUNT(id) as jumlah_pesanan'),
                DB::raw('SUM(total) as total_omset'),
                DB::raw('SUM(total_profit) as total_profit')
            )
            ->whereNull('deleted_at')
            ->where('status', 'selesai')
            ->whereMonth('created_at', $this->bulan)
            ->whereYear('created_at', $this->tahun)
            ->where('metode_pembayaran', '!=', 'komplemen')
            ->where('metode_pembayaran', '!=', 'dibatalkan')
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')
            ->get();

        // 🔹 Data komplemen (metode pembayaran = 'komplemen')
        $this->dataKomplemen = DB::table('pesanans')
            ->select(
                DB::raw('DATE(created_at) as tanggal'),
                DB::raw('COUNT(id) as jumlah_komplemen'),
                DB::raw('SUM(total) as total_komplemen'),
                DB::raw('SUM(total_profit) as total_profit_komplemen')
            )
            ->where('status', 'selesai')
            ->whereMonth('created_at', $this->bulan)
            ->whereYear('created_at', $this->tahun)
            ->where('metode_pembayaran', '=', 'komplemen')
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
            ->where('metode_pembayaran', '!=', 'dibatalkan')
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
        ]);
    }
}
