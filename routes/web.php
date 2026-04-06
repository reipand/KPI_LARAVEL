<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\SlaController;

// Auth routes
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth.session'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Task routes
    Route::resource('tasks', TaskController::class);
    Route::post('/tasks/{task}/submit', [TaskController::class, 'submit'])->name('tasks.submit');
    
    // KPI routes
    Route::get('/kpi/my', [KpiController::class, 'myKpi'])->name('kpi.my');
    Route::get('/kpi/ranking', [RankingController::class, 'index'])->name('kpi.ranking');
    Route::get('/kpi/rekap', [KpiController::class, 'rekap'])->name('kpi.rekap');
    
    // HR only routes
    Route::middleware(['hr'])->group(function () {
        Route::resource('employees', EmployeeController::class);
        Route::resource('kpi-components', KpiController::class);
        Route::resource('slas', SlaController::class);
        Route::get('/kpi/all', [KpiController::class, 'allKpi'])->name('kpi.all');
        Route::get('/kpi/mapping', [KpiController::class, 'mapping'])->name('kpi.mapping');
        Route::post('/kpi/mapping/{task}', [KpiController::class, 'saveMapping'])->name('kpi.save-mapping');
    });
}); 