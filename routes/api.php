<?php

use App\Http\Controllers\AnalyticsEventController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'data'])->name('api.dashboard');
Route::post('/events', [AnalyticsEventController::class, 'store'])->name('api.events.store');
