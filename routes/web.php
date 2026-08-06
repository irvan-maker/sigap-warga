<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\FamilyCardController;
use App\Http\Controllers\HouseholdCensusController;
use App\Http\Controllers\KelurahanReportController;
use App\Http\Controllers\KelurahanRwController;
use App\Http\Controllers\PublicReportTrackingController;
use App\Http\Controllers\ReportAttachmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RtReportController;
use App\Http\Controllers\RwReportController;
use App\Http\Controllers\RwRtController;
use App\Http\Controllers\VillageLetterController;
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
    Route::get('/admin/reports', [AdminReportController::class, 'index'])
        ->name('admin.reports.index');
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::patch('/admin/users/{user}/status', [AdminUserController::class, 'toggleActive'])
        ->name('admin.users.status.toggle');
    Route::patch('/admin/users/{user}/password', [AdminUserController::class, 'resetPassword'])
        ->name('admin.users.password.reset');
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
        Route::get('/household-census/create', [HouseholdCensusController::class, 'create'])->name('household-census.create');
        Route::post('/household-census', [HouseholdCensusController::class, 'store'])->name('household-census.store');
        Route::get('/reports/{report}', [RtReportController::class, 'show'])->name('reports.show');
        Route::patch('/reports/{report}/status', [RtReportController::class, 'updateStatus'])
            ->name('reports.status.update');
        Route::resource('citizens', CitizenController::class)->except('destroy');
        Route::patch('/citizens/{citizen}/status', [CitizenController::class, 'toggleActive'])->name('citizens.status.toggle');
        Route::get('/family-cards/{familyCard}/members/create', [FamilyCardController::class, 'createMember'])->name('family-cards.members.create');
        Route::post('/family-cards/{familyCard}/members', [FamilyCardController::class, 'storeMember'])->name('family-cards.members.store');
        Route::patch('/family-cards/{familyCard}/head/{citizen}', [FamilyCardController::class, 'setHead'])->name('family-cards.head.update');
        Route::resource('family-cards', FamilyCardController::class)->except('destroy')->parameters(['family-cards' => 'familyCard']);
        Route::patch('/family-cards/{familyCard}/status', [FamilyCardController::class, 'toggleActive'])->name('family-cards.status.toggle');
        Route::resource('letters', VillageLetterController::class)->except('destroy');
        Route::patch('/letters/{letter}/submit', [VillageLetterController::class, 'submit'])->name('letters.submit');
        Route::get('/letters/{letter}/pdf', [VillageLetterController::class, 'pdf'])->name('letters.pdf');
    });

Route::middleware(['auth', 'role.rw'])
    ->prefix('rw')
    ->name('rw.')
    ->group(function (): void {
        Route::get('/dashboard', [RwReportController::class, 'index'])->name('dashboard');
        Route::get('/rts', [RwRtController::class, 'index'])->name('rts.index');
        Route::get('/rts/create', [RwRtController::class, 'create'])->name('rts.create');
        Route::post('/rts', [RwRtController::class, 'store'])->name('rts.store');
        Route::get('/rts/{rt}/edit', [RwRtController::class, 'edit'])->name('rts.edit');
        Route::put('/rts/{rt}', [RwRtController::class, 'update'])->name('rts.update');
        Route::patch('/rts/{rt}/status', [RwRtController::class, 'toggleActive'])->name('rts.status.toggle');
        Route::get('/reports/{report}', [RwReportController::class, 'show'])->name('reports.show');
        Route::get('/citizens', [CitizenController::class, 'index'])->name('citizens.index');
        Route::get('/citizens/{citizen}', [CitizenController::class, 'show'])->name('citizens.show');
        Route::get('/family-cards', [FamilyCardController::class, 'index'])->name('family-cards.index');
        Route::get('/family-cards/{familyCard}', [FamilyCardController::class, 'show'])->name('family-cards.show');
        Route::get('/letters', [VillageLetterController::class, 'index'])->name('letters.index');
        Route::get('/letters/{letter}', [VillageLetterController::class, 'show'])->name('letters.show');
        Route::patch('/letters/{letter}/review', [VillageLetterController::class, 'review'])->name('letters.review');
        Route::patch('/letters/{letter}/reject', [VillageLetterController::class, 'reject'])->name('letters.reject');
        Route::get('/letters/{letter}/pdf', [VillageLetterController::class, 'pdf'])->name('letters.pdf');
    });

Route::middleware(['auth', 'role.kelurahan'])
    ->prefix('kelurahan')
    ->name('kelurahan.')
    ->group(function (): void {
        Route::get('/dashboard', [KelurahanReportController::class, 'index'])->name('dashboard');
        Route::get('/rws', [KelurahanRwController::class, 'index'])->name('rws.index');
        Route::get('/rws/create', [KelurahanRwController::class, 'create'])->name('rws.create');
        Route::post('/rws', [KelurahanRwController::class, 'store'])->name('rws.store');
        Route::get('/rws/{rw}/edit', [KelurahanRwController::class, 'edit'])->name('rws.edit');
        Route::put('/rws/{rw}', [KelurahanRwController::class, 'update'])->name('rws.update');
        Route::patch('/rws/{rw}/status', [KelurahanRwController::class, 'toggleActive'])->name('rws.status.toggle');
        Route::get('/reports', [KelurahanReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [KelurahanReportController::class, 'show'])->name('reports.show');
        Route::resource('citizens', CitizenController::class)->except('destroy');
        Route::patch('/citizens/{citizen}/status', [CitizenController::class, 'toggleActive'])->name('citizens.status.toggle');
        Route::get('/family-cards/{familyCard}/members/create', [FamilyCardController::class, 'createMember'])->name('family-cards.members.create');
        Route::post('/family-cards/{familyCard}/members', [FamilyCardController::class, 'storeMember'])->name('family-cards.members.store');
        Route::patch('/family-cards/{familyCard}/head/{citizen}', [FamilyCardController::class, 'setHead'])->name('family-cards.head.update');
        Route::resource('family-cards', FamilyCardController::class)->except('destroy')->parameters(['family-cards' => 'familyCard']);
        Route::patch('/family-cards/{familyCard}/status', [FamilyCardController::class, 'toggleActive'])->name('family-cards.status.toggle');
        Route::get('/letters', [VillageLetterController::class, 'index'])->name('letters.index');
        Route::get('/letters/{letter}', [VillageLetterController::class, 'show'])->name('letters.show');
        Route::patch('/letters/{letter}/approve', [VillageLetterController::class, 'approve'])->name('letters.approve');
        Route::patch('/letters/{letter}/reject', [VillageLetterController::class, 'reject'])->name('letters.reject');
        Route::patch('/letters/{letter}/issue', [VillageLetterController::class, 'issue'])->name('letters.issue');
        Route::get('/letters/{letter}/pdf', [VillageLetterController::class, 'pdf'])->name('letters.pdf');
    });
