<?php

namespace App\Livewire\Dashboard;

use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\PesananItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public $title = 'Dashboard';
    public $subTitle = 'eCommerce';

    public function render()
    {
        $omset = Pesanan::where('status', 'selesai')
            ->leftJoin('payment_methods', 'pesanans.payment_method_id', '=', 'payment_methods.id')
            ->where(function($q) {
                $q->where('payment_methods.kode_metode', '!=', 'komplemen')
                  ->orWhereNull('pesanans.payment_method_id');
            })
            ->whereDate('pesanans.created_at', Carbon::today())
            ->sum('pesanans.total');

        $sellMenu = PesananItem::whereHas('pesanan', function ($query) {
            $query->where('status', 'selesai')
                ->where(function($q) {
                    $q->whereHas('paymentMethod', function($pm) {
                        $pm->where('kode_metode', '!=', 'komplemen');
                    })->orWhereNull('payment_method_id');
                })
                ->whereDate('created_at', Carbon::today());
        })->where('qty', '>', 0)
            ->sum('qty');

        $totalOrder = Pesanan::where('status', 'selesai')
            ->leftJoin('payment_methods', 'pesanans.payment_method_id', '=', 'payment_methods.id')
            ->where(function($q) {
                $q->where('payment_methods.kode_metode', '!=', 'komplemen')
                  ->orWhereNull('pesanans.payment_method_id');
            })
            ->whereDate('pesanans.created_at', Carbon::today())
            ->count();
        $proses = Pesanan::where('status', '!=', 'selesai')->where('status', '!=', 'dibatalkan')->whereDate('created_at', Carbon::today())->count();
        
        $cards = [
            [
                'title' => 'Total Orders',
                'value' => $totalOrder,
                'icon' => 'gridicons:multiple-users',
                'color' => 'bg-blue-600',
            ],
            [
                'title' => 'Menu Terjual',
                'value' => $sellMenu,
                'icon' => 'fa-solid:award',
                'color' => 'bg-cyan-600',
            ],
            [
                'title' => 'Belum Bayar',
                'value' => $proses,
                'icon' => 'solar:wallet-bold',
                'color' => 'bg-success-600',
            ],
            [
                'title' => 'Omset',
                'value' => "Rp" . number_format($omset, 0, ',', '.'),
                'icon' => 'fa6-solid:file-invoice-dollar',
                'color' => 'bg-purple-600',
            ],
        ];

        $omsetPerTanggal = DB::table('pesanans')
            ->leftJoin('payment_methods', 'pesanans.payment_method_id', '=', 'payment_methods.id')
            ->whereNull('pesanans.deleted_at')
            ->where('pesanans.status', 'selesai')
            ->where('payment_methods.kode_metode', '!=', 'komplemen')
            ->where('pesanans.created_at', '>=', now()->subDays(9)->startOfDay())
            ->selectRaw('
                DATE(pesanans.created_at) as tanggal,
                SUM(pesanans.total - pesanans.discount_value) as omset
            ')
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->get();

        $menuTerlaris = Menu::withSum([
            'pesananItems as jumlah_terjual' => function ($q) {
                $q->whereHas('pesanan', function ($p) {
                    $p->where('status', 'selesai');
                });
            }
        ], 'qty')
            ->orderByDesc('jumlah_terjual')
            ->limit(10)
            ->get();

        $categories = $omsetPerTanggal->pluck('tanggal');
        $data = $omsetPerTanggal->pluck('omset');

        return view('livewire.dashboard.index', [
            'cards' => $cards,
            'categories' => $categories,
            'data' => $data,
            'menuTerlaris' => $menuTerlaris,
            'title' => 'Dashboard Overview'
        ])->layout('layouts.app', [
            'title' => $this->title,
            'subTitle' => $this->subTitle,
            'data' => $data,
            'categories' => $categories,
            'script' => '<script src="' . asset('assets/js/homethreeChart.js') . '"></script>'
        ]);
    }
}
