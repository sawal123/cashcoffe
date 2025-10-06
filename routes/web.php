<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MejaController;
use App\Http\Controllers\StruckController;

Route::view('/', 'welcome');

Route::controller(DashboardController::class)->group(function () {
    Route::get('/dashboard', 'index')->name('dashboard.index');
    // Route::get('/menu', 'menu')->name('menu');
});
Route::resource('menu', MenuController::class);
Route::resource('category', CategoryController::class);
Route::resource('order', OrderController::class);
Route::resource('meja', MejaController::class);
// routes/web.php
Route::get('print/struk/{id}', [StruckController::class, 'index'])->name('struk.print');


Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');



require __DIR__ . '/auth.php';
