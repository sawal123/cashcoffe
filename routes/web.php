<?php

use App\Exports\OrdersExport;
use App\Livewire\Absensi\ClockIn as AbsensiClockIn;
use App\Livewire\Absensi\Home;
use App\Livewire\Absensi\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\StruckController;

Route::view('/', 'welcome');

Route::prefix('absen')
    ->name('absensi.')
    ->middleware(['auth', 'role:karyawan'])
    ->group(function () {
        Route::get('/', Home::class)->name('home');
        Route::get('/clock-in', AbsensiClockIn::class)->name('clock.in');
    });

Route::middleware(['auth', 'role:kasir|manager|superadmin'])->group(function () {
    
    Route::get('/dashboard', App\Livewire\Dashboard\Index::class)->name('dashboard.index');

    // Menu
    Route::get('/menu', App\Livewire\Menu\TableMenu::class)->name('menu.index');
    Route::get('/menu/create', App\Livewire\Menu\Create::class)->name('menu.create');
    Route::get('/menu/{menuId}/edit', App\Livewire\Menu\Create::class)->name('menu.edit');
    Route::get('/menu/{id}/variants', App\Livewire\Variant\ManageMenuVariant::class)->name('menu.variants');

    // Order
    Route::get('/order', App\Livewire\Order\TableOrder::class)->name('order.index');
    Route::get('/order/create', App\Livewire\Order\CreateOrder::class)->name('order.create');
    Route::get('/order/{orderId}/edit', App\Livewire\Order\CreateOrder::class)->name('order.edit');

    // Meja
    Route::get('/meja', App\Livewire\Meja\IndexMeja::class)->name('meja.index');

    // Discount
    Route::get('/discount', App\Livewire\Discount\TableDiscount::class)->name('discount.index');
    Route::get('/discount/create', App\Livewire\Discount\CreateDiscount::class)->name('discount.create');
    Route::get('/discount/{id}/edit', App\Livewire\Discount\CreateDiscount::class)->name('discount.edit');
    Route::get('/discount-approval', App\Livewire\Discount\ApprovalList::class)->name('discount-approval.index');

    // Gudang
    Route::get('/gudang', App\Livewire\Gudang\TableGudang::class)->name('gudang.index');
    Route::get('/gudang/create', App\Livewire\Gudang\CreateGudang::class)->name('gudang.create');
    Route::get('/gudang/{gudangId}/edit', App\Livewire\Gudang\CreateGudang::class)->name('gudang.edit');

    // Member
    Route::get('/member', App\Livewire\Member\TableMember::class)->name('member.index');
    Route::get('/member/create', App\Livewire\Member\CreateMember::class)->name('member.create');
    Route::get('/member/{memberId}/edit', App\Livewire\Member\CreateMember::class)->name('member.edit');

    // Omset
    Route::get('/omset', App\Livewire\Omset\TableOmset::class)->name('omset.index');

    // Pengeluaran
    Route::get('/pengeluaran', App\Livewire\Pengeluaran\TablePengeluaran::class)->name('pengeluaran.index');
    Route::get('/pengeluaran/create', App\Livewire\Pengeluaran\Create::class)->name('pengeluaran.create');
    Route::get('/pengeluaran/{pengeluaranId}/edit', App\Livewire\Pengeluaran\Create::class)->name('pengeluaran.edit');

    // Transaksi
    Route::get('/transaksi', App\Livewire\Transaksi\Transaksi::class)->name('transaksi.index');

    // Riwayat Gudang
    Route::get('/riwayat-gudang', App\Livewire\RiwayatGudang\TableRiwayat::class)->name('riwayat-gudang.index');

    // Stock Dapur
    Route::get('/stock-dapur', App\Livewire\Stock\StockDapur::class)->name('stock-dapur.index');
    Route::get('/stock-dapur/create', App\Livewire\Stock\StockDapurCreate::class)->name('stock-dapur.create');
    Route::get('/stock-dapur/{stockId}/edit', App\Livewire\Stock\StockDapurCreate::class)->name('stock-dapur.edit');

    // Riwayat Stock
    Route::get('/riwayat-stock', App\Livewire\Stock\RiwayatStock::class)->name('riwayat-stock.index');
    Route::get('/riwayat-stock/create', App\Livewire\Stock\StockAdd::class)->name('riwayat-stock.create');
    Route::get('/riwayat-stock/{stockId}/edit', App\Livewire\Stock\StockAdd::class)->name('riwayat-stock.edit');

    // Absense
    Route::get('/absense', App\Livewire\Absense\TableAbsense::class)->name('absense.index');
    Route::get('/absense/{userId}', App\Livewire\Absense\ShowAbsense::class)->name('absense.show');

    // User
    Route::get('/user', App\Livewire\User\TableUser::class)->name('user.index');
    Route::get('/user/create', App\Livewire\User\CreateUser::class)->name('user.create');
    Route::get('/user/{userId}/edit', App\Livewire\User\CreateUser::class)->name('user.edit');

    Route::get('print/struk/{id}', [StruckController::class, 'index'])->name('struk.print');

    Route::get('/orders/export', function () {
        return Excel::download(new OrdersExport, 'laporan-orders.xlsx');
    })->name('orders.export');

    Route::middleware(['role:superadmin'])->group(function () {
        Route::get('/branch', App\Livewire\Branch\Index::class)->name('branch.index');
        Route::get('/price-tier', App\Livewire\Tier\Index::class)->name('price-tier.index');
        Route::get('/category', App\Livewire\Category\TableCategory::class)->name('category.index');
        Route::get('/category/create', App\Livewire\Category\CategoryCreate::class)->name('category.create');
        Route::get('/category/{categoryId}/edit', App\Livewire\Category\CategoryCreate::class)->name('category.edit');
        Route::get('/menu-ingredient', App\Livewire\Menu\MenuIngredient::class)->name('menu-ingredient.index');
        Route::get('/variant-group', App\Livewire\Variant\TableVariantGroup::class)->name('variant-group.index');
    });

    Route::middleware(['role:manager'])->group(function () {
        Route::get('/menu-cabang', App\Livewire\Branch\MenuAvailability::class)->name('menu-cabang.index');
    });
});

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');

require __DIR__.'/auth.php';

Route::get('/reverse-geocode', function (Request $request) {
    if (! $request->lat || ! $request->lon) {
        return response()->json(['error' => 'Latitude & longitude wajib'], 400);
    }
    $url = 'https://nominatim.openstreetmap.org/reverse';
    $response = Http::withHeaders([
        'User-Agent' => 'absensi-app/1.0 (admin@localhost)',
    ])->get($url, [
        'format' => 'json',
        'lat' => $request->lat,
        'lon' => $request->lon,
    ]);
    return $response->json();
});
