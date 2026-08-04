<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\PublicReportTrackingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RtReportController;
use App\Http\Controllers\RwReportController;
use App\Http\Controllers\KelurahanReportController;
use App\Http\Controllers\ReportAttachmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/tracking', [PublicReportTrackingController::class, 'index'])
    ->name('tracking.index');
Route::post('/tracking', [PublicReportTrackingController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('tracking.store');

Route::get('/report-attachments/{attachment}', [ReportAttachmentController::class, 'show'])
    ->middleware('signed')
    ->name('report-attachments.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'role.rt'])
    ->prefix('rt')
    ->name('rt.')
    ->group(function (): void {
        Route::get('/dashboard', [RtReportController::class, 'index'])->name('dashboard');
        Route::get('/reports/{report}', [RtReportController::class, 'show'])->name('reports.show');
        Route::patch('/reports/{report}/status', [RtReportController::class, 'updateStatus'])
            ->name('reports.status.update');
    });

Route::middleware(['auth', 'role.rw'])
    ->prefix('rw')
    ->name('rw.')
    ->group(function (): void {
        Route::get('/dashboard', [RwReportController::class, 'index'])->name('dashboard');
        Route::get('/reports/{report}', [RwReportController::class, 'show'])->name('reports.show');
    });

Route::middleware('role.kelurahan')
    ->prefix('kelurahan')
    ->name('kelurahan.')
    ->group(function (): void {
        Route::get('/dashboard', [KelurahanReportController::class, 'index'])->name('dashboard');
        Route::get('/reports/{report}', [KelurahanReportController::class, 'show'])->name('reports.show');
    });
