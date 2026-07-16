<?php

use App\Http\Controllers\AdditionalItemController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestRentalController;
use App\Http\Controllers\ManualRentalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\PublicMahjongTournamentController;
use App\Http\Controllers\RentalPromoController;
use App\Http\Controllers\RentalHistoryController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LogController;
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

// Route::middleware('guest')->group(function () {
//     Route::get('/login', [LoginController::class, 'create'])->name('login');
//     Route::post('/login', [LoginController::class, 'store'])
//         ->middleware('throttle:10,1')
//         ->name('login.store');
// });
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('login.store');

Route::get('/', [PublicMahjongTournamentController::class, 'index'])
    ->name('home');
Route::get('/turnamen/mahjong', [PublicMahjongTournamentController::class, 'index'])
    ->name('public.mahjong-tournaments');
Route::get('/turnamen/mahjong/{id}/daftar', [PublicMahjongTournamentController::class, 'showRegister'])
    ->whereNumber('id')
    ->name('public.mahjong-tournaments.register');
Route::post('/turnamen/mahjong/{id}/daftar/cek', [PublicMahjongTournamentController::class, 'checkRegister'])
    ->whereNumber('id')
    ->middleware('throttle:20,1')
    ->name('public.mahjong-tournaments.register.check');
Route::get('/turnamen/mahjong/{id}/daftar/formulir', [PublicMahjongTournamentController::class, 'showRegisterForm'])
    ->whereNumber('id')
    ->name('public.mahjong-tournaments.register.form');
Route::get('/turnamen/mahjong/{id}/daftar/status', [PublicMahjongTournamentController::class, 'showRegisterStatus'])
    ->whereNumber('id')
    ->name('public.mahjong-tournaments.register.status');
Route::post('/turnamen/mahjong/{id}/daftar/bukti-bayar', [PublicMahjongTournamentController::class, 'uploadPaymentReceipt'])
    ->whereNumber('id')
    ->middleware('throttle:10,1')
    ->name('public.mahjong-tournaments.register.receipt');
Route::post('/turnamen/mahjong/{id}/daftar', [PublicMahjongTournamentController::class, 'submitRegister'])
    ->whereNumber('id')
    ->middleware('throttle:10,1')
    ->name('public.mahjong-tournaments.register.store');
Route::get('/turnamen/mahjong/{id}/peringkat', [PublicMahjongTournamentController::class, 'standings'])
    ->whereNumber('id')
    ->name('public.mahjong-tournaments.standings');
Route::get('/turnamen/mahjong/{id}/juara', [PublicMahjongTournamentController::class, 'winners'])
    ->whereNumber('id')
    ->name('public.mahjong-tournaments.winners');

Route::prefix('guest')->name('guest.')->group(function () {
    Route::get('/sewa', function () {
        if (auth()->check()) {
            return redirect()->route('rental.index');
        }

        return redirect()->route('home', request()->query());
    })->name('rental.index');
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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/toko', [TokoController::class, 'index'])->name('toko.index');
        Route::post('/toko', [TokoController::class, 'store'])->name('toko.store');
        Route::put('/toko/{toko}', [TokoController::class, 'update'])->name('toko.update');
        Route::delete('/toko/{toko}', [TokoController::class, 'destroy'])->name('toko.destroy');

        Route::get('/additional-items', [AdditionalItemController::class, 'index'])->name('additional-items.index');
        Route::post('/additional-items', [AdditionalItemController::class, 'store'])->name('additional-items.store');
        Route::put('/additional-items/{additionalItem}', [AdditionalItemController::class, 'update'])->name('additional-items.update');
        Route::delete('/additional-items/{additionalItem}', [AdditionalItemController::class, 'destroy'])->name('additional-items.destroy');

        Route::get('/rental-promos', [RentalPromoController::class, 'index'])->name('rental-promos.index');
        Route::post('/rental-promos', [RentalPromoController::class, 'store'])->name('rental-promos.store');
        Route::put('/rental-promos/{rentalPromo}', [RentalPromoController::class, 'update'])->name('rental-promos.update');
        Route::delete('/rental-promos/{rentalPromo}', [RentalPromoController::class, 'destroy'])->name('rental-promos.destroy');

        Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
        Route::get('/logs/{log}', [LogController::class, 'show'])->name('logs.show');
    });

    Route::get('/sewa', [RentalController::class, 'index'])->name('rental.index');
    Route::post('/sewa', [RentalController::class, 'store'])->name('rental.store');
    Route::get('/sewa/manual', [ManualRentalController::class, 'index'])->name('rental.manual.index');
    Route::post('/sewa/manual', [ManualRentalController::class, 'store'])->name('rental.manual.store');
    Route::get('/sewa/riwayat', [RentalHistoryController::class, 'index'])->name('rental.history.index');
    Route::get('/sewa/riwayat/data', [RentalHistoryController::class, 'data'])->name('rental.history.data');
    Route::get('/sewa/riwayat/{rental}', [RentalHistoryController::class, 'show'])->name('rental.history.show');
    Route::put('/sewa/riwayat/{rental}', [RentalHistoryController::class, 'update'])->name('rental.history.update');
    Route::delete('/sewa/riwayat/{rental}', [RentalHistoryController::class, 'destroy'])
        ->middleware('admin')
        ->name('rental.history.destroy');
    Route::get('/sewa/{rental}/invoice', [RentalController::class, 'invoice'])->name('rental.invoice');
    Route::get('/sewa/{rental}/bukti', [RentalController::class, 'showBukti'])->name('rental.bukti');
    Route::match(['get', 'post'], '/sewa/{rental}/checkout-preview', [RentalController::class, 'checkoutPreview'])->name('rental.checkout-preview');

    Route::post('/sewa/{rental}/checkout', [RentalController::class, 'checkout'])->name('rental.checkout');
    Route::delete('/sewa/{rental}/cancel', [RentalController::class, 'cancel'])->name('rental.cancel');

    Route::get('/cashflow/laporan', [CashFlowController::class, 'report'])->name('cashflow.report');
    Route::get('/cashflow/{cashFlow}/invoice', [CashFlowController::class, 'invoice'])->name('cashflow.invoice');
    Route::get('/cashflow', [CashFlowController::class, 'index'])->name('cashflow.index');
    Route::patch('/cashflow/{cashFlow}/metode-pembayaran', [CashFlowController::class, 'updatePaymentMethod'])->name('cashflow.update-metode-pembayaran');
    Route::get('/cashflow/{cashFlow}/bukti', [CashFlowController::class, 'showBukti'])->name('cashflow.bukti');
    Route::post('/cashflow/{cashFlow}/bukti', [CashFlowController::class, 'uploadBukti'])->name('cashflow.upload-bukti');
});
