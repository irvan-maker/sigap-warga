<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminPosyanduController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminServiceEntryPointController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\FamilyCardController;
use App\Http\Controllers\HouseholdCensusController;
use App\Http\Controllers\KelurahanReportController;
use App\Http\Controllers\KelurahanRwController;
use App\Http\Controllers\LetterFieldDefinitionController;
use App\Http\Controllers\LetterRequirementController;
use App\Http\Controllers\LetterTypeDefinitionController;
use App\Http\Controllers\LetterTypeVersionController;
use App\Http\Controllers\LetterWorkflowStepController;
use App\Http\Controllers\PosyanduController;
use App\Http\Controllers\PublicLetterTrackingController;
use App\Http\Controllers\PublicPortalController;
use App\Http\Controllers\PublicPrivacyController;
use App\Http\Controllers\PublicReportTrackingController;
use App\Http\Controllers\ReportAttachmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RtReportController;
use App\Http\Controllers\RwReportController;
use App\Http\Controllers\RwRtController;
use App\Http\Controllers\ServiceGatewayController;
use App\Http\Controllers\VillageLetterController;
use App\Http\Controllers\WhatsAppIntegrationController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicPortalController::class)->name('public.home');
Route::get('/privasi', PublicPrivacyController::class)->name('public.privacy');

Route::get('/q/{entryToken}', [ServiceGatewayController::class, 'show'])
    ->middleware('module:quick_report')
    ->name('service-gateway.show');
Route::post('/q/{entryToken}/whatsapp', [ServiceGatewayController::class, 'whatsapp'])
    ->middleware(['module:quick_report', 'throttle:60,1'])
    ->name('service-gateway.whatsapp');

Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])
    ->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])
    ->name('webhooks.whatsapp.receive');

Route::get('/tracking', [PublicReportTrackingController::class, 'index'])
    ->name('tracking.index');
Route::post('/tracking', [PublicReportTrackingController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('tracking.store');

Route::middleware('module:letters')->group(function (): void {
    Route::get('/lacak-surat', [PublicLetterTrackingController::class, 'index'])
        ->name('letter-tracking.index');
    Route::post('/lacak-surat', [PublicLetterTrackingController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('letter-tracking.store');
    Route::get('/lacak-surat/download/{trackingCode}', [PublicLetterTrackingController::class, 'download'])
        ->middleware('signed')
        ->name('letter-tracking.download');
});

Route::get('/report-attachments/{attachment}', [ReportAttachmentController::class, 'show'])
    ->middleware('signed')
    ->name('report-attachments.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    Route::get('/admin/integrations/whatsapp', WhatsAppIntegrationController::class)
        ->name('admin.whatsapp-integration.index');
    Route::get('/admin/reports', [AdminReportController::class, 'index'])
        ->name('admin.reports.index');
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::middleware('module:quick_report')->group(function (): void {
        Route::get('/admin/service-entry-points', [AdminServiceEntryPointController::class, 'index'])
            ->name('admin.service-entry-points.index');
        Route::post('/admin/service-entry-points', [AdminServiceEntryPointController::class, 'store'])
            ->name('admin.service-entry-points.store');
        Route::patch('/admin/service-entry-points/{entryPoint}/revoke', [AdminServiceEntryPointController::class, 'revoke'])
            ->name('admin.service-entry-points.revoke');
    });
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

    Route::middleware('module:posyandu')->group(function (): void {
        Route::get('/posyandu', [PosyanduController::class, 'index'])->name('posyandu.index');
        Route::post('/posyandu/schedules', [PosyanduController::class, 'storeSchedule'])->name('posyandu.schedules.store');
        Route::post('/posyandu/visits', [PosyanduController::class, 'storeVisit'])->name('posyandu.visits.store');
        Route::get('/admin/posyandu', [AdminPosyanduController::class, 'index'])->name('admin.posyandu.index');
        Route::post('/admin/posyandu/sites', [AdminPosyanduController::class, 'storeSite'])->name('admin.posyandu.sites.store');
        Route::post('/admin/posyandu/staff', [AdminPosyanduController::class, 'storeStaff'])->name('admin.posyandu.staff.store');
    });
});

Route::middleware(['auth', 'role.rt'])
    ->prefix('rt')
    ->name('rt.')
    ->group(function (): void {
        Route::get('/dashboard', [RtReportController::class, 'index'])->name('dashboard');
        Route::middleware('module:census')->group(function (): void {
            Route::get('/household-census/create', [HouseholdCensusController::class, 'create'])->name('household-census.create');
            Route::post('/household-census', [HouseholdCensusController::class, 'store'])->name('household-census.store');
        });
        Route::get('/reports/{report}', [RtReportController::class, 'show'])->name('reports.show');
        Route::patch('/reports/{report}/status', [RtReportController::class, 'updateStatus'])
            ->name('reports.status.update');
        Route::post('/reports/{report}/acknowledge', [RtReportController::class, 'acknowledge'])
            ->name('reports.acknowledge');
        Route::post('/reports/{report}/forward', [RtReportController::class, 'forward'])
            ->name('reports.forward');
        Route::resource('citizens', CitizenController::class)->except('destroy');
        Route::patch('/citizens/{citizen}/status', [CitizenController::class, 'toggleActive'])->name('citizens.status.toggle');
        Route::get('/family-cards/{familyCard}/members/create', [FamilyCardController::class, 'createMember'])->name('family-cards.members.create');
        Route::post('/family-cards/{familyCard}/members', [FamilyCardController::class, 'storeMember'])->name('family-cards.members.store');
        Route::patch('/family-cards/{familyCard}/head/{citizen}', [FamilyCardController::class, 'setHead'])->name('family-cards.head.update');
        Route::resource('family-cards', FamilyCardController::class)->except('destroy')->parameters(['family-cards' => 'familyCard']);
        Route::patch('/family-cards/{familyCard}/status', [FamilyCardController::class, 'toggleActive'])->name('family-cards.status.toggle');
        Route::middleware('module:letters')->group(function (): void {
            Route::resource('letters', VillageLetterController::class)->except('destroy');
            Route::patch('/letters/{letter}/submit', [VillageLetterController::class, 'submit'])->name('letters.submit');
            Route::patch('/letters/{letter}/issue', [VillageLetterController::class, 'issue'])->name('letters.issue');
            Route::get('/letters/{letter}/pdf', [VillageLetterController::class, 'pdf'])->name('letters.pdf');
        });
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
        Route::get('/rts/{rt}/officers', [\App\Http\Controllers\RwRtOfficerController::class, 'index'])->name('rts.officers.index');
        Route::post('/rts/{rt}/officers', [\App\Http\Controllers\RwRtOfficerController::class, 'store'])->name('rts.officers.store');
        Route::put('/rts/{rt}/officers/{officer}', [\App\Http\Controllers\RwRtOfficerController::class, 'update'])->name('rts.officers.update');
        Route::patch('/rts/{rt}/officers/{officer}/status', [\App\Http\Controllers\RwRtOfficerController::class, 'toggleActive'])->name('rts.officers.status.toggle');
        Route::patch('/rts/{rt}/officers/{officer}/password', [\App\Http\Controllers\RwRtOfficerController::class, 'resetPassword'])->name('rts.officers.password.reset');
        Route::get('/reports/{report}', [RwReportController::class, 'show'])->name('reports.show');
        Route::post('/reports/{report}/acknowledge', [RwReportController::class, 'acknowledge'])->name('reports.acknowledge');
        Route::post('/reports/{report}/forward', [RwReportController::class, 'forward'])->name('reports.forward');
        Route::patch('/reports/{report}/status', [RwReportController::class, 'updateStatus'])->name('reports.status.update');
        Route::get('/citizens', [CitizenController::class, 'index'])->name('citizens.index');
        Route::get('/citizens/{citizen}', [CitizenController::class, 'show'])->name('citizens.show');
        Route::get('/family-cards', [FamilyCardController::class, 'index'])->name('family-cards.index');
        Route::get('/family-cards/{familyCard}', [FamilyCardController::class, 'show'])->name('family-cards.show');
        Route::middleware('module:letters')->group(function (): void {
            Route::get('/letters', [VillageLetterController::class, 'index'])->name('letters.index');
            Route::get('/letters/{letter}', [VillageLetterController::class, 'show'])->name('letters.show');
            Route::patch('/letters/{letter}/review', [VillageLetterController::class, 'review'])->name('letters.review');
            Route::patch('/letters/{letter}/reject', [VillageLetterController::class, 'reject'])->name('letters.reject');
            Route::patch('/letters/{letter}/issue', [VillageLetterController::class, 'issue'])->name('letters.issue');
            Route::get('/letters/{letter}/pdf', [VillageLetterController::class, 'pdf'])->name('letters.pdf');
        });
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
        Route::post('/reports/{report}/acknowledge', [KelurahanReportController::class, 'acknowledge'])->name('reports.acknowledge');
        Route::patch('/reports/{report}/status', [KelurahanReportController::class, 'updateStatus'])->name('reports.status.update');
        Route::resource('citizens', CitizenController::class)->except('destroy');
        Route::patch('/citizens/{citizen}/status', [CitizenController::class, 'toggleActive'])->name('citizens.status.toggle');
        Route::get('/family-cards/{familyCard}/members/create', [FamilyCardController::class, 'createMember'])->name('family-cards.members.create');
        Route::post('/family-cards/{familyCard}/members', [FamilyCardController::class, 'storeMember'])->name('family-cards.members.store');
        Route::patch('/family-cards/{familyCard}/head/{citizen}', [FamilyCardController::class, 'setHead'])->name('family-cards.head.update');
        Route::resource('family-cards', FamilyCardController::class)->except('destroy')->parameters(['family-cards' => 'familyCard']);
        Route::patch('/family-cards/{familyCard}/status', [FamilyCardController::class, 'toggleActive'])->name('family-cards.status.toggle');
        Route::middleware('module:letters')->group(function (): void {
            Route::get('/master-jenis-surat', [LetterTypeDefinitionController::class, 'index'])
                ->name('letter-types.index');
            Route::get('/master-jenis-surat/create', [LetterTypeDefinitionController::class, 'create'])
                ->name('letter-types.create');
            Route::post('/master-jenis-surat', [LetterTypeDefinitionController::class, 'store'])
                ->name('letter-types.store');
            Route::get('/master-jenis-surat/{letterType}/edit', [LetterTypeDefinitionController::class, 'edit'])
                ->name('letter-types.edit');
            Route::put('/master-jenis-surat/{letterType}', [LetterTypeDefinitionController::class, 'update'])
                ->name('letter-types.update');
            Route::post('/master-jenis-surat/{letterType}/versions', [LetterTypeVersionController::class, 'store'])
                ->name('letter-types.versions.store');

            Route::get('/master-jenis-surat/versions/{letterTypeVersion}', [LetterTypeVersionController::class, 'show'])
                ->name('letter-type-versions.show');
            Route::post('/master-jenis-surat/versions/{letterTypeVersion}/publish', [LetterTypeVersionController::class, 'publish'])
                ->name('letter-type-versions.publish');
            Route::delete('/master-jenis-surat/versions/{letterTypeVersion}', [LetterTypeVersionController::class, 'destroy'])
                ->name('letter-type-versions.destroy');

            Route::post('/master-jenis-surat/versions/{letterTypeVersion}/requirements', [LetterRequirementController::class, 'store'])
                ->name('letter-type-versions.requirements.store');
            Route::put('/master-jenis-surat/versions/{letterTypeVersion}/requirements/{letterRequirement}', [LetterRequirementController::class, 'update'])
                ->name('letter-type-versions.requirements.update');
            Route::delete('/master-jenis-surat/versions/{letterTypeVersion}/requirements/{letterRequirement}', [LetterRequirementController::class, 'destroy'])
                ->name('letter-type-versions.requirements.destroy');

            Route::post('/master-jenis-surat/versions/{letterTypeVersion}/fields', [LetterFieldDefinitionController::class, 'store'])
                ->name('letter-type-versions.fields.store');
            Route::put('/master-jenis-surat/versions/{letterTypeVersion}/fields/{letterFieldDefinition}', [LetterFieldDefinitionController::class, 'update'])
                ->name('letter-type-versions.fields.update');
            Route::delete('/master-jenis-surat/versions/{letterTypeVersion}/fields/{letterFieldDefinition}', [LetterFieldDefinitionController::class, 'destroy'])
                ->name('letter-type-versions.fields.destroy');

            Route::post('/master-jenis-surat/versions/{letterTypeVersion}/workflow', [LetterWorkflowStepController::class, 'store'])
                ->name('letter-type-versions.workflow.store');
            Route::put('/master-jenis-surat/versions/{letterTypeVersion}/workflow/{letterWorkflowStep}', [LetterWorkflowStepController::class, 'update'])
                ->name('letter-type-versions.workflow.update');
            Route::delete('/master-jenis-surat/versions/{letterTypeVersion}/workflow/{letterWorkflowStep}', [LetterWorkflowStepController::class, 'destroy'])
                ->name('letter-type-versions.workflow.destroy');

            Route::get('/letters', [VillageLetterController::class, 'index'])->name('letters.index');
            Route::get('/letters/{letter}', [VillageLetterController::class, 'show'])->name('letters.show');
            Route::patch('/letters/{letter}/approve', [VillageLetterController::class, 'approve'])->name('letters.approve');
            Route::patch('/letters/{letter}/reject', [VillageLetterController::class, 'reject'])->name('letters.reject');
            Route::patch('/letters/{letter}/issue', [VillageLetterController::class, 'issue'])->name('letters.issue');
            Route::get('/letters/{letter}/pdf', [VillageLetterController::class, 'pdf'])->name('letters.pdf');
        });
    });
