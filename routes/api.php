<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'data'])->name('api.dashboard');
Route::post('/transactions', [TransactionController::class, 'store'])->name('api.transactions.store');
