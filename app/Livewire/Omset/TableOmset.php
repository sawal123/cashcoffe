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
        $this->dataOmset = DB::table('pesanans')
            ->select(
                DB::raw('DATE(created_at) as tanggal'),
                DB::raw('COUNT(id) as jumlah_pesanan'),
                DB::raw('SUM(total) as total_omset'),
                DB::raw('SUM(total_profit) as total_profit')
            )
            ->where('status', 'selesai')
            ->whereMonth('created_at', $this->bulan)
            ->whereYear('created_at', $this->tahun)
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')
            ->get();

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

        // Gabungkan hasil berdasarkan tanggal
        $this->merged = $this->dataOmset->map(function ($item) {
            $qty = $this->dataQty->firstWhere('tanggal', $item->tanggal);
            $item->jumlah_menu = $qty->jumlah_menu ?? 0;
            return $item;
        });
    }


    public function render()
    {
        $totalOmset = $this->dataOmset->sum('total_omset');
        $totalProfit = $this->dataOmset->sum('total_profit');

        return view('livewire.omset.table-omset', [
            'dataOmset' => $this->merged, // hasil gabungan
            'totalOmset' => $totalOmset,
            'totalProfit' => $totalProfit,
        ]);
    }
}
