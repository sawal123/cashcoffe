<?php

namespace App\Http\Controllers;

use App\Models\Menu;
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
            ->where('metode_pembayaran', '!=', 'komplemen')
            ->whereDate('created_at', Carbon::today())
            ->sum('total');

        $sellMenu = PesananItem::whereHas('pesanan', function ($query) {
            $query->where('status', 'selesai')
                ->where('metode_pembayaran', '!=', 'komplemen')
                ->whereDate('created_at', Carbon::today());
        })->where('qty', '>', 0)
            ->sum('qty');

        $totalOrder = Pesanan::where('status', 'selesai')->where('metode_pembayaran', '!=', 'komplemen')->whereDate('created_at', Carbon::today())->count();
        $proses = Pesanan::where('status', '!=', 'selesai')->where('status', '!=', 'dibatalkan')->whereDate('created_at', Carbon::today())->count();
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
            ->whereNull('deleted_at') // ⬅️ INI PENTING
            ->where('status', 'selesai')
            ->whereNotNull('metode_pembayaran')
            ->where('metode_pembayaran', '!=', 'komplemen')
            ->where('created_at', '>=', now()->subDays(9)->startOfDay())
            ->selectRaw('
        DATE(created_at) as tanggal,
        SUM(total - discount_value) as omset
    ')
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->get();
        // dd($omsetPerTanggal);
        $menuTerlaris = Menu::withSum(['pesananItems as jumlah_terjual' => function ($q) {
            $q->whereHas('pesanan', function ($p) {
                $p->where('status', 'selesai');
            });
        }], 'qty')
            ->orderByDesc('jumlah_terjual')
            ->limit(10)
            ->get();


        // Data untuk chart
        $categories = $omsetPerTanggal->pluck('tanggal'); // untuk x-axis
        $data = $omsetPerTanggal->pluck('omset'); // untuk y-axis
        // dd($categories, $data);
        return view('dashboard.dashboard', [
            'cards' => $cards,
            'categories' => $categories,
            'data' => $data,
            'menuTerlaris' => $menuTerlaris
        ]);
    }
}
