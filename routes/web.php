<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\GuestRentalController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

Route::prefix('guest')->name('guest.')->group(function () {
    Route::get('/sewa', [GuestRentalController::class, 'index'])->name('rental.index');
    Route::get('/sewa/active', [GuestRentalController::class, 'active'])->name('rental.active');
    Route::post('/sewa/start', [GuestRentalController::class, 'start'])
        ->middleware('throttle:30,1')
        ->name('rental.start');
    Route::get('/sewa/rental/{rental}/preview', [GuestRentalController::class, 'checkoutPreview'])->name('rental.checkout-preview');
    Route::post('/sewa/rental/{rental}/stop', [GuestRentalController::class, 'stop'])
        ->middleware('throttle:30,1')
        ->name('rental.stop');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('index');
    });

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/toko', [TokoController::class, 'index'])->name('toko.index');
    Route::post('/toko', [TokoController::class, 'store'])->name('toko.store');
    Route::put('/toko/{toko}', [TokoController::class, 'update'])->name('toko.update');
    Route::delete('/toko/{toko}', [TokoController::class, 'destroy'])->name('toko.destroy');

    Route::get('/sewa', [RentalController::class, 'index'])->name('rental.index');
    Route::post('/sewa', [RentalController::class, 'store'])->name('rental.store');
    Route::get('/sewa/{rental}/checkout-preview', [RentalController::class, 'checkoutPreview'])->name('rental.checkout-preview');
    Route::post('/sewa/{rental}/checkout', [RentalController::class, 'checkout'])->name('rental.checkout');

    Route::get('/cashflow/{cashFlow}/invoice', [CashFlowController::class, 'invoice'])->name('cashflow.invoice');
    Route::get('/cashflow', [CashFlowController::class, 'index'])->name('cashflow.index');
    Route::patch('/cashflow/{cashFlow}/metode-pembayaran', [CashFlowController::class, 'updatePaymentMethod'])->name('cashflow.update-metode-pembayaran');
    Route::get('/cashflow/{cashFlow}/bukti', [CashFlowController::class, 'showBukti'])->name('cashflow.bukti');
    Route::post('/cashflow/{cashFlow}/bukti', [CashFlowController::class, 'uploadBukti'])->name('cashflow.upload-bukti');
});
