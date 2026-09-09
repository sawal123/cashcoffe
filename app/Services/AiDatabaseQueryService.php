<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Branch;
use App\Models\Ingredients;
use App\Models\Member;
use App\Models\Menu;
use App\Models\PaymentMethod;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\RiwayatStock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AiDatabaseQueryService
{
    public function answer(string $reportType, array $payload, string $userQuery = ''): ?string
    {
        return match (strtolower($reportType)) {
            'transaction_detail' => $this->transactionDetail($payload, $userQuery),
            'member_info' => $this->memberInfo($payload, $userQuery),
            'payment_methods' => $this->paymentMethods($payload, $userQuery),
            'stock_history' => $this->stockHistory($payload, $userQuery),
            'menu_sales' => $this->menuSales($payload),
            'top_selling_menus' => $this->topSellingMenus($payload),
            'least_selling_menus' => $this->leastSellingMenus($payload),
            'sales_summary' => $this->salesSummary($payload),
            'inventory_stock' => $this->inventoryStock($payload, $userQuery),
            'employee_attendance' => $this->employeeAttendance($payload),
            'menu_ingredients' => $this->menuIngredients($payload, $userQuery),
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

    private function leastSellingMenus(array $payload): string
    {
        [$startDate, $endDate, $periodLabel] = $this->period($payload);
        $limit = max(1, min((int) ($payload['limit'] ?? 5), 10));

        $branchId = null;
        if ($branchName = trim((string) ($payload['branch_name'] ?? ''))) {
            $branchId = \App\Models\Branch::where('nama_cabang', 'like', '%'.$branchName.'%')->value('id');
        }

        $items = Menu::query()
            ->select('menus.id', 'menus.nama_menu')
            ->selectRaw('COALESCE(SUM(pesanan_items.qty), 0) as total_qty')
            ->selectRaw('COALESCE(SUM(pesanan_items.subtotal), 0) as total_sales')
            ->leftJoin('pesanan_items', function ($join) {
                $join->on('menus.id', '=', 'pesanan_items.menus_id')
                    ->whereNull('pesanan_items.deleted_at');
            })
            ->leftJoin('pesanans', function ($join) use ($startDate, $endDate, $branchId) {
                $join->on('pesanan_items.pesanans_id', '=', 'pesanans.id')
                    ->where('pesanans.status', '=', 'selesai')
                    ->whereNull('pesanans.deleted_at');
                if ($startDate) {
                    $join->whereDate('pesanans.created_at', '>=', $startDate);
                }
                if ($endDate) {
                    $join->whereDate('pesanans.created_at', '<=', $endDate);
                }
                if ($branchId) {
                    $join->where('pesanans.branch_id', '=', $branchId);
                }
            })
            ->groupBy('menus.id', 'menus.nama_menu')
            ->orderBy('total_qty', 'asc')
            ->limit($limit)
            ->get();

        if ($items->isEmpty()) {
            return 'Belum ada data menu di database.';
        }

        $rows = $items->values()->map(function ($item, $index) {
            return ($index + 1).'. '.$item->nama_menu
                .' — '.number_format((int) $item->total_qty, 0, ',', '.').' item'
                .' (Rp'.number_format((float) $item->total_sales, 0, ',', '.').')';
        })->implode("\n");

        return "Menu paling sedikit dipesan ({$periodLabel}):\n{$rows}";
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

    private function inventoryStock(array $payload, string $userQuery = ''): string
    {
        $itemName = trim((string) ($payload['item_name'] ?? ''));
        $queryLower = mb_strtolower($userQuery);

        $query = Ingredients::query()->with(['satuan', 'branch']);

        if (str_contains($queryLower, 'habis') || str_contains($queryLower, 'kosong')) {
            $query->where('stok', '<=', 0);
        } elseif (str_contains($queryLower, 'minus') || str_contains($queryLower, 'negatif')) {
            $query->where('stok', '<', 0);
        } elseif (str_contains($queryLower, 'sedikit') || str_contains($queryLower, 'menipis') || str_contains($queryLower, 'hampir habis') || str_contains($queryLower, 'rendah')) {
            $query->where('stok', '>', 0)->where('stok', '<=', 5);
        } elseif ($itemName !== '') {
            $query->where('nama_bahan', 'like', '%'.$itemName.'%');
        }

        if ($branchName = trim((string) ($payload['branch_name'] ?? ''))) {
            $query->whereHas('branch', fn (Builder $query) => $query->where('nama_cabang', 'like', '%'.$branchName.'%'));
        }

        $items = $query->orderBy('nama_bahan')->limit(10)->get();

        if ($items->isEmpty()) {
            if (str_contains($queryLower, 'habis') || str_contains($queryLower, 'kosong')) {
                return 'Tidak ada stok bahan baku yang habis saat ini.';
            }
            if (str_contains($queryLower, 'minus')) {
                return 'Tidak ada stok bahan baku yang bernilai minus saat ini.';
            }
            if (str_contains($queryLower, 'sedikit') || str_contains($queryLower, 'menipis')) {
                return 'Tidak ada stok bahan baku yang menipis saat ini.';
            }

            return 'Data stok yang diminta tidak ditemukan.';
        }

        $header = 'Stok bahan saat ini:';
        if (str_contains($queryLower, 'habis')) {
            $header = 'Daftar stok bahan baku yang habis (<= 0):';
        } elseif (str_contains($queryLower, 'minus')) {
            $header = 'Daftar stok bahan baku yang minus (< 0):';
        } elseif (str_contains($queryLower, 'sedikit') || str_contains($queryLower, 'menipis')) {
            $header = 'Daftar stok bahan baku yang menipis (<= 5):';
        }

        $list = $items->map(function ($item) {
            $unit = $item->satuan?->nama_satuan ?? 'unit';
            $branch = $item->branch?->nama_cabang ?? 'Tanpa cabang';

            return '- '.$item->nama_bahan.': '.number_format((float) $item->stok, 0, ',', '.')." {$unit} ({$branch})";
        })->implode("\n");

        return "{$header}\n{$list}\nLink: /stock-dapur";
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

    private function menuIngredients(array $payload, string $userQuery): string
    {
        $query = mb_strtolower($userQuery);

        // If the query asks for menus without ingredients/compositions (e.g. "belum memiliki komposisi", "tanpa komposisi", "tidak ada bahan")
        if (str_contains($query, 'belum') || str_contains($query, 'tanpa') || str_contains($query, 'tidak memiliki') || str_contains($query, 'tidak ada')) {
            $menus = Menu::doesntHave('ingredients')->get();
            if ($menus->isEmpty()) {
                return 'Semua menu sudah memiliki komposisi bahan baku.';
            }
            $list = $menus->map(fn ($m) => "- {$m->nama_menu}")->implode("\n");

            return "Berikut adalah menu yang belum memiliki komposisi bahan baku:\n".$list;
        }

        // Extract menu name from payload or query string
        $menuName = trim((string) ($payload['menu_name'] ?? ''));
        if ($menuName === '' || strcasecmp($menuName, 'none') === 0) {
            $menu = Menu::query()->get(['nama_menu'])
                ->first(fn ($menu) => str_contains($query, mb_strtolower($menu->nama_menu)));
            if ($menu) {
                $menuName = $menu->nama_menu;
            }
        }

        if ($menuName !== '') {
            $menu = Menu::where('nama_menu', 'like', '%'.$menuName.'%')->with('ingredients.satuan')->first();
            if (! $menu) {
                return "Menu '{$menuName}' tidak ditemukan.";
            }
            if ($menu->ingredients->isEmpty()) {
                return "Menu '{$menu->nama_menu}' belum memiliki komposisi bahan baku.";
            }
            $list = $menu->ingredients->map(function ($i) {
                $qty = $i->pivot->qty ?? 0;
                $satuan = $i->satuan->nama_satuan ?? 'unit';

                return "- {$i->nama_bahan}: ".number_format($qty, 0, ',', '.')." {$satuan}";
            })->implode("\n");

            return "Komposisi bahan baku untuk menu {$menu->nama_menu}:\n".$list;
        }

        return 'Sebutkan nama menu yang ingin dicek komposisinya atau tanyakan menu yang belum memiliki komposisi.';
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

    private function transactionDetail(array $payload, string $userQuery): string
    {
        $invoice = trim((string) ($payload['invoice_number'] ?? ''));

        if ($invoice === '' || strcasecmp($invoice, 'none') === 0) {
            if (preg_match('/([A-Za-z0-9]+(?:-[A-Za-z0-9]+)+)/', $userQuery, $matches)) {
                $invoice = $matches[1];
            } else {
                $trimmed = trim($userQuery);
                if (Pesanan::where('kode', $trimmed)->exists()) {
                    $invoice = $trimmed;
                }
            }
        }

        if ($invoice === '') {
            return 'Sebutkan nomor invoice atau kode transaksi yang ingin dicek.';
        }

        $pesanan = Pesanan::query()
            ->with([
                'items.menu',
                'items.variants',
                'user',
                'branch',
                'salesChannel',
                'paymentMethod',
                'member.user',
                'discount',
            ])
            ->where('kode', $invoice)
            ->first()
            ?? Pesanan::query()
                ->with([
                    'items.menu',
                    'items.variants',
                    'user',
                    'branch',
                    'salesChannel',
                    'paymentMethod',
                    'member.user',
                    'discount',
                ])
                ->where('kode', 'like', '%'.$invoice.'%')
                ->first();

        if (! $pesanan) {
            return "Transaksi dengan invoice '{$invoice}' tidak ditemukan.";
        }

        $dateFormatted = Carbon::parse($pesanan->created_at)->translatedFormat('d M Y H:i');
        $customer = $pesanan->nama ?: ($pesanan->member?->user?->name ?? '-');
        $status = ucfirst($pesanan->status);
        $kasir = $pesanan->user?->name ?? '-';
        $cabang = $pesanan->branch?->nama_cabang ?? ($pesanan->user?->branch?->nama_cabang ?? '-');
        $channel = $pesanan->salesChannel?->nama_channel ?? '-';
        $payment = $pesanan->paymentMethod?->nama_metode ?? ($pesanan->metode_pembayaran ?: '-');

        $lines = [];
        $lines[] = 'Detail Transaksi';
        $lines[] = "Invoice: {$pesanan->kode}";
        $lines[] = "Tanggal: {$dateFormatted}";
        $lines[] = "Customer: {$customer}";
        $lines[] = "Status: {$status}";
        $lines[] = "Kasir: {$kasir}";
        $lines[] = "Cabang: {$cabang}";
        $lines[] = "Sales Channel: {$channel}";
        $lines[] = "Pembayaran: {$payment}";

        if ($pesanan->member) {
            $memberName = $pesanan->member->user?->name ?? $pesanan->member->phone ?? '-';
            $lines[] = "Member: {$memberName}";
        }

        if ($pesanan->discount) {
            $discName = $pesanan->discount->nama_diskon ?? 'Diskon';
            $lines[] = "Diskon: {$discName}";
        }

        if ($pesanan->catatan) {
            $lines[] = "Catatan: {$pesanan->catatan}";
        }

        $lines[] = 'Pesanan:';
        foreach ($pesanan->items as $item) {
            $menuName = $item->menu?->nama_menu ?? 'Item';
            $variants = $item->variants->pluck('nama_opsi')->filter()->implode(', ');
            $variantSuffix = $variants !== '' ? " ({$variants})" : '';
            $lines[] = "{$menuName}{$variantSuffix} × {$item->qty}";

            $unitPrice = number_format((float) $item->harga_satuan, 0, ',', '.');
            $subtotal = number_format((float) $item->subtotal, 0, ',', '.');
            if ($item->qty > 1) {
                $lines[] = "Rp{$unitPrice} × {$item->qty} = Rp{$subtotal}";
            } else {
                $lines[] = "Rp{$subtotal}";
            }
        }

        $totalBefore = (float) $pesanan->total;
        $discountVal = (float) ($pesanan->discount_value ?? 0);
        $totalAkhir = $totalBefore - $discountVal;

        $lines[] = 'Total: Rp'.number_format($totalBefore, 0, ',', '.');
        $lines[] = 'Diskon: Rp'.number_format($discountVal, 0, ',', '.');
        $lines[] = 'Total Akhir: Rp'.number_format($totalAkhir, 0, ',', '.');

        if ($pesanan->uang_tunai && (float) $pesanan->uang_tunai > 0) {
            $lines[] = 'Uang Tunai: Rp'.number_format((float) $pesanan->uang_tunai, 0, ',', '.');
            $lines[] = 'Kembalian: Rp'.number_format((float) ($pesanan->kembalian ?? 0), 0, ',', '.');
        }

        $lines[] = 'Link: /transaksi';
        $lines[] = "Cetak Struk: /print/struk/{$pesanan->id}";

        return implode("\n", $lines);
    }

    private function memberInfo(array $payload, string $userQuery): string
    {
        $queryLower = mb_strtolower($userQuery);

        $isListAll = str_contains($queryLower, 'semua member')
            || str_contains($queryLower, 'daftar member')
            || str_contains($queryLower, 'jumlah member')
            || str_contains($queryLower, 'banyak member')
            || str_contains($queryLower, 'pengeluaran terbesar')
            || str_contains($queryLower, 'belanja terbanyak')
            || str_contains($queryLower, 'top member');

        $memberName = trim((string) ($payload['member_name'] ?? ''));
        $memberPhone = trim((string) ($payload['member_phone'] ?? ''));
        $memberEmail = trim((string) ($payload['member_email'] ?? ''));

        if (! $isListAll && $memberName === '' && $memberPhone === '' && $memberEmail === '') {
            if (preg_match('/(08\d{8,12}|\+62\d{8,12})/', $userQuery, $m)) {
                $memberPhone = $m[1];
            } elseif (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $userQuery, $m)) {
                $memberEmail = $m[1];
            } else {
                $clean = preg_replace('/(cari member|informasi member|detail member|info member|berapa poin member|poin member|total belanja member|total belanja|member)\s*/i', '', $userQuery);
                $clean = trim($clean, " ?.\t\n\r\0\x0B");
                if ($clean !== '' && mb_strlen($clean) >= 2) {
                    $memberName = $clean;
                }
            }
        }

        if ($isListAll || ($memberName === '' && $memberPhone === '' && $memberEmail === '')) {
            $totalMembers = Member::count();
            if ($totalMembers === 0) {
                return 'Belum ada member yang terdaftar di database.';
            }

            $members = Member::with('user')
                ->orderByDesc('total_pengeluaran')
                ->limit(10)
                ->get();

            $lines = ['Total member: '.number_format($totalMembers, 0, ',', '.')];
            foreach ($members as $m) {
                $name = $m->user?->name ?? 'Member';
                $points = number_format((int) $m->points, 0, ',', '.');
                $spending = number_format((float) $m->total_pengeluaran, 0, ',', '.');
                $lines[] = "{$name} — {$points} poin — Rp{$spending}";
            }
            $lines[] = 'Link: /member';

            return implode("\n", $lines);
        }

        $memberQuery = Member::query()->with('user');

        if ($memberPhone !== '') {
            $cleanPhone = preg_replace('/[^0-9]/', '', $memberPhone);
            $normalizedPhone = \App\Support\PhoneNumber::member($cleanPhone);
            $lookupValues = \App\Support\PhoneNumber::lookupValues($cleanPhone);

            $memberQuery->where(function ($q) use ($memberPhone, $cleanPhone, $normalizedPhone, $lookupValues) {
                if (! empty($lookupValues)) {
                    $q->whereIn('phone', $lookupValues);
                }
                if ($normalizedPhone !== '') {
                    $q->orWhere('phone', 'like', '%'.$normalizedPhone.'%');
                }
                $q->orWhere('phone', 'like', '%'.$cleanPhone.'%')
                    ->orWhere('phone', 'like', '%'.$memberPhone.'%');
            });
        } elseif ($memberEmail !== '') {
            $memberQuery->whereHas('user', fn ($q) => $q->where('email', 'like', '%'.$memberEmail.'%'));
        } elseif ($memberName !== '') {
            $memberQuery->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$memberName.'%'));
        }

        $member = $memberQuery->first();

        if (! $member) {
            $searchKey = $memberName ?: ($memberPhone ?: $memberEmail);

            return "Member '{$searchKey}' tidak ditemukan.";
        }

        $user = $member->user;
        $name = $user?->name ?? '-';
        $email = $user?->email ?? '-';
        $rawPhone = $member->phone ?: ($user?->phone ?? '-');
        $phone = $rawPhone;
        if ($phone !== '-' && ! str_starts_with($phone, '0') && ! str_starts_with($phone, '+')) {
            $phone = '0'.$phone;
        }
        $address = $member->address ?: ($user?->alamat ?? ($user?->address ?? '-'));
        $points = number_format((int) $member->points, 0, ',', '.');
        $spending = number_format((float) $member->total_pengeluaran, 0, ',', '.');

        $totalOrders = Pesanan::where('member_id', $member->id)->count();
        $completedOrders = Pesanan::where('member_id', $member->id)->where('status', 'selesai')->count();
        $lastOrder = Pesanan::where('member_id', $member->id)->latest('created_at')->first();
        $lastOrderText = $lastOrder
            ? Carbon::parse($lastOrder->created_at)->translatedFormat('d M Y H:i')." ({$lastOrder->kode})"
            : '-';

        $lines = [
            'Informasi Member',
            "Nama: {$name}",
            "Email: {$email}",
            "Telepon: {$phone}",
            "Alamat: {$address}",
            "Poin: {$points}",
            "Total Pengeluaran: Rp{$spending}",
            "Jumlah Transaksi: ".number_format($totalOrders, 0, ',', '.')." (Selesai: {$completedOrders})",
            "Transaksi Terakhir: {$lastOrderText}",
            "Link: /member/{$member->id}/edit",
        ];

        return implode("\n", $lines);
    }

    private function paymentMethods(array $payload, string $userQuery): string
    {
        $queryLower = mb_strtolower($userQuery);

        $isStats = str_contains($queryLower, 'transaksi')
            || str_contains($queryLower, 'omzet')
            || str_contains($queryLower, 'penjualan')
            || str_contains($queryLower, 'banyak digunakan')
            || str_contains($queryLower, 'terbanyak')
            || str_contains($queryLower, 'populer')
            || str_contains($queryLower, 'penggunaan')
            || ! empty($payload['date_from'])
            || ! empty($payload['date_to']);

        $methodName = trim((string) ($payload['payment_method'] ?? ''));
        if ($methodName === '' || strcasecmp($methodName, 'none') === 0) {
            foreach (['qris', 'tunai', 'cash', 'transfer', 'kartu', 'shopeefood', 'gofood', 'grabfood', 'komplemen'] as $m) {
                if (str_contains($queryLower, $m)) {
                    $methodName = $m;
                    break;
                }
            }
        }

        // Case 1: Simple status check, e.g. "apakah QRIS aktif"
        if (! $isStats && (str_contains($queryLower, 'aktif') || str_contains($queryLower, 'status') || $methodName !== '')) {
            if ($methodName !== '') {
                $target = PaymentMethod::query()
                    ->where('nama_metode', 'like', '%'.$methodName.'%')
                    ->orWhere('kode_metode', 'like', '%'.$methodName.'%')
                    ->first();

                if ($target) {
                    $status = $target->is_active ? 'Aktif' : 'Nonaktif';

                    return "Metode Pembayaran {$target->nama_metode}:\n"
                        ."- Kode: {$target->kode_metode}\n"
                        ."- Status: {$status}\n"
                        .'Link: /payment-method';
                }
            }
        }

        // Case 2: Statistics / Transactions / Omzet
        if ($isStats) {
            [$startDate, $endDate, $periodLabel] = $this->period($payload);

            $branchId = null;
            if ($branchName = trim((string) ($payload['branch_name'] ?? ''))) {
                $branchId = Branch::where('nama_cabang', 'like', '%'.$branchName.'%')->value('id');
            }

            $ordersQuery = Pesanan::query()
                ->where('status', 'selesai')
                ->whereNotNull('payment_method_id')
                ->when($startDate, fn (Builder $q) => $q->whereDate('created_at', '>=', $startDate))
                ->when($endDate, fn (Builder $q) => $q->whereDate('created_at', '<=', $endDate))
                ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId));

            if ($methodName !== '') {
                $targetMethodIds = PaymentMethod::where('nama_metode', 'like', '%'.$methodName.'%')
                    ->orWhere('kode_metode', 'like', '%'.$methodName.'%')
                    ->pluck('id');
                $ordersQuery->whereIn('payment_method_id', $targetMethodIds);
            }

            $stats = $ordersQuery
                ->selectRaw('payment_method_id, COUNT(*) as total_orders, COALESCE(SUM(total - COALESCE(discount_value, 0)), 0) as omzet')
                ->groupBy('payment_method_id')
                ->orderByDesc('omzet')
                ->with('paymentMethod')
                ->get();

            if ($stats->isEmpty()) {
                return "Belum ada transaksi selesai menggunakan metode pembayaran untuk periode {$periodLabel}.";
            }

            $totalAllOrders = $stats->sum('total_orders');

            $lines = ["Penggunaan Metode Pembayaran — {$periodLabel}"];
            foreach ($stats as $st) {
                $pName = $st->paymentMethod?->nama_metode ?? 'Metode #'.$st->payment_method_id;
                $tCount = number_format((int) $st->total_orders, 0, ',', '.');
                $omzetFormatted = number_format((float) $st->omzet, 0, ',', '.');
                $percent = $totalAllOrders > 0 ? round(($st->total_orders / $totalAllOrders) * 100, 1) : 0;

                $lines[] = "{$pName}";
                $lines[] = "- Transaksi: {$tCount} ({$percent}%)";
                $lines[] = "- Omzet: Rp{$omzetFormatted}";
            }
            $lines[] = 'Link: /payment-method';

            return implode("\n", $lines);
        }

        // Case 3: List all payment methods
        $allMethods = PaymentMethod::orderBy('nama_metode')->get();
        if ($allMethods->isEmpty()) {
            return 'Belum ada metode pembayaran yang terdaftar di database.';
        }

        $lines = ['Metode Pembayaran'];
        foreach ($allMethods as $pm) {
            $status = $pm->is_active ? 'Aktif' : 'Nonaktif';
            $lines[] = "{$pm->nama_metode}";
            $lines[] = "Kode: {$pm->kode_metode}";
            $lines[] = "Status: {$status}";
        }
        $lines[] = 'Link: /payment-method';

        return implode("\n", $lines);
    }

    private function stockHistory(array $payload, string $userQuery): string
    {
        $queryLower = mb_strtolower($userQuery);
        $itemName = trim((string) ($payload['item_name'] ?? ''));

        if ($itemName === '' || strcasecmp($itemName, 'none') === 0) {
            $ingredient = Ingredients::get(['nama_bahan'])
                ->first(fn ($ing) => str_contains($queryLower, mb_strtolower($ing->nama_bahan)));
            if ($ingredient) {
                $itemName = $ingredient->nama_bahan;
            }
        }

        [$startDate, $endDate, $periodLabel] = $this->period($payload);

        $stockType = strtolower(trim((string) ($payload['stock_type'] ?? '')));
        if ($stockType === '' || ! in_array($stockType, ['in', 'out'], true)) {
            if (str_contains($queryLower, 'stok keluar') || str_contains($queryLower, 'keluar') || str_contains($queryLower, 'berkurang')) {
                $stockType = 'out';
            } elseif (str_contains($queryLower, 'stok masuk') || str_contains($queryLower, 'masuk') || str_contains($queryLower, 'penambahan')) {
                $stockType = 'in';
            }
        }

        $limit = max(1, min((int) ($payload['limit'] ?? 10), 10));

        $historyQuery = RiwayatStock::query()
            ->with(['ingredient.satuan', 'branch', 'toBranch'])
            ->when($startDate, fn (Builder $q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn (Builder $q) => $q->whereDate('created_at', '<=', $endDate))
            ->when($stockType !== '', fn (Builder $q) => $q->where('tipe', $stockType));

        $targetIngredient = null;
        if ($itemName !== '') {
            $targetIngredient = Ingredients::with('satuan')->where('nama_bahan', 'like', '%'.$itemName.'%')->first();
            if ($targetIngredient) {
                $historyQuery->where('ingredient_id', $targetIngredient->id);
            }
        }

        if ($branchName = trim((string) ($payload['branch_name'] ?? ''))) {
            $historyQuery->whereHas('branch', fn (Builder $q) => $q->where('nama_cabang', 'like', '%'.$branchName.'%'));
        }

        $records = $historyQuery->orderByDesc('created_at')->orderByDesc('id')->limit($limit)->get();

        if ($records->isEmpty()) {
            $itemLabel = $targetIngredient ? " untuk {$targetIngredient->nama_bahan}" : '';

            return "Riwayat stok{$itemLabel} tidak ditemukan untuk periode {$periodLabel}.";
        }

        $isWhyQuestion = str_contains($queryLower, 'kenapa') || str_contains($queryLower, 'mengapa') || str_contains($queryLower, 'sebab');

        $lines = [];
        if ($targetIngredient) {
            $unit = $targetIngredient->satuan?->nama_satuan ?? 'unit';
            $lines[] = "Riwayat Stock — {$targetIngredient->nama_bahan}";
            $lines[] = 'Stok sekarang: '.number_format((float) $targetIngredient->stok, 0, ',', '.')." {$unit}";
        } else {
            $lines[] = "Riwayat Stock ({$periodLabel})";
        }

        if ($isWhyQuestion) {
            $lines[] = 'Penyebab pengurangan stok terakhir:';
        }

        foreach ($records as $rec) {
            $date = Carbon::parse($rec->created_at)->translatedFormat('d M Y H:i');
            $tipeUpper = strtoupper($rec->tipe);
            $unit = $rec->ingredient?->satuan?->nama_satuan ?? 'unit';
            $ingName = $targetIngredient ? '' : ($rec->ingredient?->nama_bahan ?? 'Bahan').' — ';

            $lines[] = "{$date}";
            $lines[] = "{$ingName}{$tipeUpper} ".number_format((float) $rec->qty, 0, ',', '.')." {$unit}";
            $lines[] = number_format((float) $rec->qty_before, 0, ',', '.')." {$unit} → "
                .number_format((float) $rec->qty_after, 0, ',', '.')." {$unit}";

            $ket = $rec->keterangan ?: 'Tanpa keterangan';
            $lines[] = "Keterangan: {$ket}";

            if (preg_match('/(?:pesanan|invoice)\s*([A-Za-z0-9\-_]+)/i', $ket, $match)) {
                $lines[] = "Invoice terkait: {$match[1]}";
            }
        }

        $lines[] = 'Link: /riwayat-stock';

        return implode("\n", $lines);
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
