<?php

use App\Exports\OrdersExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MejaController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OmsetController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\StruckController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\RiwayatGudangController;

Route::view('/', 'welcome');

Route::get('/orders/export', function () {
    return Excel::download(new OrdersExport, 'laporan-orders.xlsx');
})->name('orders.export');
Route::middleware(['auth'])->group(function () {
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard.index');
    });

    Route::resource('menu', MenuController::class);

    Route::resource('order', OrderController::class);
    Route::resource('meja', MejaController::class);
    Route::resource('category', CategoryController::class);
    Route::resource('discount', DiscountController::class);
    Route::resource('gudang', GudangController::class);
    Route::resource('omset', OmsetController::class);
    Route::resource('pengeluaran', PengeluaranController::class);
    Route::resource('transaksi', TransaksiController::class);
    Route::resource('riwayat-gudang', RiwayatGudangController::class);
    Route::get('print/struk/{id}', [StruckController::class, 'index'])->name('struk.print');

    Route::middleware(['role:admin'])->group(function () {});
});

// routes/web.php

Route::post('/logout', function () {
    Auth::logout();

    return redirect('/login');
})->name('logout');

require __DIR__ . '/auth.php';
