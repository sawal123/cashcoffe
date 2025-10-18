<?php

namespace App\Livewire\Omset;

use App\Models\Pesanan;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class TableOmset extends Component
{
    public $bulan;
    public $tahun;
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
            ->join('pesanan_items', 'pesanan_items.pesanans_id', '=', 'pesanans.id')
            ->select(
                DB::raw('DATE(pesanans.created_at) as tanggal'),
                DB::raw('COUNT(DISTINCT pesanans.id) as jumlah_pesanan'),
                DB::raw('SUM(pesanan_items.qty) as jumlah_menu'),
                DB::raw('SUM(pesanans.total) as total_omset'),
                DB::raw('SUM(pesanans.total_profit) as total_profit')
            )
            ->where('pesanans.status', 'selesai')
            ->whereMonth('pesanans.created_at', $this->bulan)
            ->whereYear('pesanans.created_at', $this->tahun)
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->get();
    }

    public function render()
    {
        $totalOmset = $this->dataOmset->sum('total_omset');
        $totalProfit = $this->dataOmset->sum('total_profit');

        return view('livewire.omset.table-omset', [
            'dataOmset' => $this->dataOmset,
            'totalOmset' => $totalOmset,
            'totalProfit' => $totalProfit,
        ]);
    }
}
