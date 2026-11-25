<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CoupleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExchangeController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

    Route::resource('transaksi', TransactionController::class)
        ->except('show')
        ->parameters(['transaksi' => 'transaction'])
        ->names('transactions');

    Route::get('/tukar', [ExchangeController::class, 'create'])->name('exchange.create');
    Route::post('/tukar', [ExchangeController::class, 'store'])->name('exchange.store');

    Route::resource('target', GoalController::class)
        ->parameters(['target' => 'goal'])
        ->names('goals');

    Route::get('/berdua', CoupleController::class)->name('couple');

    Route::get('/kurs', [RateController::class, 'index'])->name('rates.index');
    Route::put('/kurs', [RateController::class, 'update'])->name('rates.update');
    Route::delete('/kurs/{code}', [RateController::class, 'clear'])->name('rates.clear');
    Route::post('/kurs/refresh', [RateController::class, 'refresh'])->name('rates.refresh');

    Route::get('/akun', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/akun/{user}/setujui', [AccountController::class, 'approve'])->name('accounts.approve');
    Route::delete('/akun/{user}', [AccountController::class, 'reject'])->name('accounts.reject');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/sandi', [ProfileController::class, 'password'])->name('profile.password');
});
