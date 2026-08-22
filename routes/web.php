<?php

use App\Http\Controllers\{AuthController, DashboardController};
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
    Route::get('/join', [AuthController::class, 'register'])->name('register');
    Route::post('/join', [AuthController::class, 'save']);
});
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/standings', [DashboardController::class, 'standings'])->name('standings');
    Route::get('/live', [DashboardController::class, 'live'])->name('live');
    Route::get('/managers', [DashboardController::class, 'managers'])->name('managers');
    Route::get('/managers/{entry}', [DashboardController::class, 'manager'])->name('managers.show');
    Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');
    Route::get('/hall-of-fame', [DashboardController::class, 'hall'])->name('hall');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
