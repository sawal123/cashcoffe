<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Ingredients;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AiDatabaseQueryService
{
    public function answer(string $reportType, array $payload): ?string
    {
        return match (strtolower($reportType)) {
            'menu_sales' => $this->menuSales($payload),
            'top_selling_menus' => $this->topSellingMenus($payload),
            'sales_summary' => $this->salesSummary($payload),
            'inventory_stock' => $this->inventoryStock($payload),
            'employee_attendance' => $this->employeeAttendance($payload),
            default => null,
        };
    }

    private function menuSales(array $payload): string
    {
        $menuName = trim((string) ($payload['menu_name'] ?? ''));

        if ($menuName === '') {
            return 'Sebutkan nama menu yang ingin dicek penjualannya.';
        }

        $menu = Menu::query()
            ->whereRaw('LOWER(nama_menu) = ?', [mb_strtolower($menuName)])
            ->first()
            ?? Menu::query()->where('nama_menu', 'like', '%'.$menuName.'%')->first();

        if (! $menu) {
            return "Menu '{$menuName}' tidak ditemukan di database.";
        }

        [$startDate, $endDate, $periodLabel] = $this->period($payload);

        $query = PesananItem::query()
            ->where('menus_id', $menu->id)
            ->whereHas('pesanan', function (Builder $query) use ($payload, $startDate, $endDate) {
                $this->completedOrderScope($query, $payload, $startDate, $endDate);
            });

        $result = $query
            ->selectRaw('COALESCE(SUM(qty), 0) as total_qty')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as total_sales')
            ->selectRaw('COUNT(DISTINCT pesanans_id) as total_orders')
            ->first();

        return "Penjualan menu {$menu->nama_menu} ({$periodLabel}):\n"
            .'- Terjual: '.number_format((int) $result->total_qty, 0, ',', '.')." item\n"
            .'- Jumlah transaksi: '.number_format((int) $result->total_orders, 0, ',', '.')."\n"
            .'- Omzet item: Rp'.number_format((float) $result->total_sales, 0, ',', '.');
    }

    private function topSellingMenus(array $payload): string
    {
        [$startDate, $endDate, $periodLabel] = $this->period($payload);
        $limit = max(1, min((int) ($payload['limit'] ?? 5), 10));

        $items = PesananItem::query()
            ->selectRaw('menus_id, SUM(qty) as total_qty, SUM(subtotal) as total_sales')
            ->whereHas('pesanan', function (Builder $query) use ($payload, $startDate, $endDate) {
                $this->completedOrderScope($query, $payload, $startDate, $endDate);
            })
            ->with('menu:id,nama_menu')
            ->groupBy('menus_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();

        if ($items->isEmpty()) {
            return "Belum ada transaksi selesai untuk periode {$periodLabel}.";
        }

        $rows = $items->values()->map(function ($item, $index) {
            $menuName = $item->menu?->nama_menu ?? 'Menu tidak diketahui';

            return ($index + 1).'. '.$menuName
                .' — '.number_format((int) $item->total_qty, 0, ',', '.').' item'
                .' (Rp'.number_format((float) $item->total_sales, 0, ',', '.').')';
        })->implode("\n");

        return "Menu terlaris ({$periodLabel}):\n{$rows}";
    }

    private function salesSummary(array $payload): string
    {
        [$startDate, $endDate, $periodLabel] = $this->period($payload);

        $orders = Pesanan::query();
        $this->completedOrderScope($orders, $payload, $startDate, $endDate);

        $summary = (clone $orders)
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(total_profit), 0) as profit')
            ->first();

        $itemQty = PesananItem::query()
            ->whereHas('pesanan', function (Builder $query) use ($payload, $startDate, $endDate) {
                $this->completedOrderScope($query, $payload, $startDate, $endDate);
            })
            ->sum('qty');

        return "Ringkasan penjualan ({$periodLabel}):\n"
            .'- Transaksi selesai: '.number_format((int) $summary->total_orders, 0, ',', '.')."\n"
            .'- Item terjual: '.number_format((int) $itemQty, 0, ',', '.')."\n"
            .'- Omzet: Rp'.number_format((float) $summary->revenue, 0, ',', '.')."\n"
            .'- Laba tercatat: Rp'.number_format((float) $summary->profit, 0, ',', '.');
    }

    private function inventoryStock(array $payload): string
    {
        $itemName = trim((string) ($payload['item_name'] ?? ''));
        $query = Ingredients::query()->with(['satuan', 'branch']);

        if ($itemName !== '') {
            $query->where('nama_bahan', 'like', '%'.$itemName.'%');
        }

        if ($branchName = trim((string) ($payload['branch_name'] ?? ''))) {
            $query->whereHas('branch', fn (Builder $query) => $query->where('nama_cabang', 'like', '%'.$branchName.'%'));
        }

        $items = $query->orderBy('nama_bahan')->limit(10)->get();

        if ($items->isEmpty()) {
            return 'Data stok yang diminta tidak ditemukan.';
        }

        return "Stok bahan saat ini:\n".$items->map(function ($item) {
            $unit = $item->satuan?->nama_satuan ?? 'unit';
            $branch = $item->branch?->nama_cabang ?? 'Tanpa cabang';

            return '- '.$item->nama_bahan.': '.number_format((float) $item->stok, 0, ',', '.')." {$unit} ({$branch})";
        })->implode("\n");
    }

    private function employeeAttendance(array $payload): string
    {
        $employeeName = trim((string) ($payload['employee_name'] ?? ''));

        if ($employeeName === '') {
            return 'Sebutkan nama karyawan yang ingin dicek absensinya.';
        }

        $employee = User::query()->where('name', 'like', '%'.$employeeName.'%')->first();

        if (! $employee) {
            return "Karyawan '{$employeeName}' tidak ditemukan.";
        }

        [$startDate, $endDate, $periodLabel] = $this->period($payload);

        $records = Absensi::query()
            ->where('user_id', $employee->id)
            ->when($startDate, fn (Builder $query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn (Builder $query) => $query->whereDate('tanggal', '<=', $endDate))
            ->get();

        $statuses = $records->groupBy('status')
            ->map(fn ($items, $status) => ucfirst($status).': '.$items->count())
            ->values()
            ->implode(', ');

        return "Absensi {$employee->name} ({$periodLabel}): "
            .($statuses !== '' ? $statuses : 'belum ada data.');
    }

    private function completedOrderScope(
        Builder $query,
        array $payload,
        ?string $startDate,
        ?string $endDate
    ): void {
        $query->where('status', 'selesai')
            ->when($startDate, fn (Builder $query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn (Builder $query) => $query->whereDate('created_at', '<=', $endDate));

        if ($branchName = trim((string) ($payload['branch_name'] ?? ''))) {
            $query->whereHas('branch', fn (Builder $query) => $query->where('nama_cabang', 'like', '%'.$branchName.'%'));
        }
    }

    private function period(array $payload): array
    {
        $startDate = $this->validDate($payload['date_from'] ?? null);
        $endDate = $this->validDate($payload['date_to'] ?? null);

        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        if ($startDate && $endDate) {
            $label = Carbon::parse($startDate)->translatedFormat('d M Y')
                .' s/d '.Carbon::parse($endDate)->translatedFormat('d M Y');
        } elseif ($startDate) {
            $label = 'sejak '.Carbon::parse($startDate)->translatedFormat('d M Y');
        } elseif ($endDate) {
            $label = 'sampai '.Carbon::parse($endDate)->translatedFormat('d M Y');
        } else {
            $label = 'semua waktu';
        }

        return [$startDate, $endDate, $label];
    }

    private function validDate(mixed $date): ?string
    {
        if (! is_string($date) || trim($date) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($date))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
