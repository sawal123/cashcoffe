<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\PesananItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $omset = Pesanan::where('status', 'selesai')
            ->whereDate('created_at', Carbon::today())
            ->sum('total');

        $sellMenu = PesananItem::whereHas('pesanan', function ($query) {
            $query->where('status', 'selesai')
                ->whereDate('created_at', Carbon::today());
        })->where('qty', '>', 0)
            ->sum('qty');

        $totalOrder = Pesanan::where('status', 'selesai')->whereDate('created_at', Carbon::today())->count();
        $proses = Pesanan::where('status', '!=', 'selesai')->whereDate('created_at', Carbon::today())->count();
        $cards = [
            [
                'title' => 'Total Orders',
                'value' => $totalOrder,
                'icon'  => 'gridicons:multiple-users',
                'color' => 'bg-blue-600',
            ],
            [
                'title' => 'Menu Terjual',
                'value' => $sellMenu,
                'icon'  => 'fa-solid:award',
                'color' => 'bg-cyan-600',
            ],
            [
                'title' => 'Belum Bayar',
                'value' => $proses,
                'icon'  => 'solar:wallet-bold',
                'color' => 'bg-success-600',
            ],
            [
                'title' => 'Omset',
                'value' => "Rp" . number_format($omset, 0, ',', '.'),
                'icon'  => 'fa6-solid:file-invoice-dollar',
                'color' => 'bg-purple-600',
            ],
        ];

        $omsetPerTanggal = DB::table('pesanans')
            ->where('status', 'selesai')
            ->selectRaw('DATE(created_at) as tanggal, SUM(total) as omset')
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')   // urutkan dari terbaru
            ->limit(10)                    // ambil 10 terakhir
            ->get()
            ->sortBy('tanggal')            // balik lagi biar urut ASC
            ->values();

        // Data untuk chart
        $categories = $omsetPerTanggal->pluck('tanggal'); // untuk x-axis
        $data = $omsetPerTanggal->pluck('omset'); // untuk y-axis
        // dd($categories, $data);
        return view('dashboard.dashboard', [
            'cards' => $cards,
            'categories' => $categories,
            'data' => $data,
        ]);
    }
}
