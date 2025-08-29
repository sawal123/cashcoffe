<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $omset = Pesanan::where('status', 'selesai')
            ->whereDate('created_at', Carbon::today())
            ->sum('total');
        // dd($omset);
        $cards = [
            [
                'title' => 'Total Orders',
                'value' => 150,
                'icon'  => 'gridicons:multiple-users',
                'color' => 'bg-blue-600',
            ],
            [
                'title' => 'Menu Terjual',
                'value' => '$12,345',
                'icon'  => 'fa-solid:award',
                'color' => 'bg-cyan-600',
            ],
            [
                'title' => 'Top Menu',
                'value' => 8,
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
        return view('dashboard.dashboard', compact('cards'));
    }
}
