<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RivalryController;
use App\Http\Controllers\TeamLabController;
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
    Route::get('/team-lab', [TeamLabController::class, 'index'])->name('team-lab');
    Route::get('/team-lab/captaincy', [TeamLabController::class, 'captaincy'])->name('team-lab.captaincy');
    Route::post('/team-lab/link', [TeamLabController::class, 'link'])->name('team-lab.link');
    Route::post('/team-lab/plans', [TeamLabController::class, 'savePlan'])->name('team-lab.plans');
    Route::get('/players/{player}', [TeamLabController::class, 'player'])->name('players.show');
    Route::get('/managers', [DashboardController::class, 'managers'])->name('managers');
    Route::get('/managers/{entry}', [DashboardController::class, 'manager'])->name('managers.show');
    Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');
    Route::get('/hall-of-fame', [DashboardController::class, 'hall'])->name('hall');
    Route::get('/rivalries', [RivalryController::class, 'index'])->name('rivalries');
    Route::get('/competitions', [CompetitionController::class, 'index'])->name('competitions');
    Route::post('/competitions', [CompetitionController::class, 'store']);
    Route::get('/competitions/{competition}', [CompetitionController::class, 'show'])->name('competitions.show');
    Route::get('/awards/{award}/card', [AwardController::class, 'show'])->name('awards.show');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
