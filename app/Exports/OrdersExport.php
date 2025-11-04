<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\Pesanan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        // Kamu bisa ubah query sesuai kebutuhan, misalnya hanya status "selesai"
        return Pesanan::where('status', 'selesai')
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'Kode',
            'Nama',
            'Status',
            'Metode Pembayaran',
            'Total',
            'Kasir',
            'Tanggal Dibuat',
        ];
    }

    public function map($order): array
    {
        return [
            $order->kode,
            $order->nama ?? '-',
            ucfirst($order->status),
            ucfirst($order->metode_pembayaran ?? 'Belum Bayar'),
            'Rp ' . number_format($order->total - $order->discount_value, 0, ',', '.'),
            $order->user->name ?? '-',
            $order->created_at->format('d M Y | H:i'),
        ];
    }
}
