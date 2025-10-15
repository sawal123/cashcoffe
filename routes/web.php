<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\MejaController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StruckController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

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
    Route::get('print/struk/{id}', [StruckController::class, 'index'])->name('struk.print');

    Route::middleware(['role:admin'])->group(function () {
    });
});

// routes/web.php

Route::post('/logout', function () {
    Auth::logout();

    return redirect('/login');
})->name('logout');

require __DIR__ . '/auth.php';
