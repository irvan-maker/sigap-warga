<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Http\Requests\AcknowledgeReportRequest;
use App\Http\Requests\UpdateHandledReportStatusRequest;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\PosyanduVisit;
use App\Models\Report;
use App\Models\ReportHistory;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\VillageLetter;
use App\Services\ReportStatusService;
use App\Services\ReportWorkflowService;
use App\Services\VillageAnalyticsService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KelurahanReportController extends Controller
{
    public function index(Request $request, VillageAnalyticsService $analyticsService): View
    {
        $today = now();
        $staleThreshold = now()->subDays(3);
        $rws = Rw::query()->orderBy('code')->get(['id', 'code', 'name', 'is_active']);
        $rts = Rt::query()->orderBy('rw_id')->orderBy('code')->get([
            'id',
            'rw_id',
            'code',
            'name',
            'is_active',
        ]);

        $counts = Report::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $countsByRw = Report::query()
            ->join('rts', 'reports.rt_id', '=', 'rts.id')
            ->selectRaw('rts.rw_id as rw_id, COUNT(reports.id) as aggregate')
            ->groupBy('rts.rw_id')
            ->pluck('aggregate', 'rw_id');

        $countsByRt = Report::query()
            ->selectRaw('rt_id, COUNT(*) as aggregate')
            ->groupBy('rt_id')
            ->pluck('aggregate', 'rt_id');

        $todayCreatedReports = Report::query()
            ->whereBetween('reported_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->count();
        $todayCompletedReports = ReportHistory::query()
            ->where('new_status', ReportStatus::COMPLETED)
            ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->distinct()
            ->count('report_id');
        $staleProcessingReports = Report::query()
            ->where('status', ReportStatus::PROCESSING)
            ->whereDoesntHave(
                'histories',
                fn (Builder $query): Builder => $query->where('created_at', '>', $staleThreshold),
            )
            ->count();
        $overdueResponseReports = Report::query()
            ->whereNull('acknowledged_at')
            ->whereNotNull('response_due_at')
            ->where('response_due_at', '<', $today)
            ->whereNotIn('status', [ReportStatus::COMPLETED, ReportStatus::REJECTED])
            ->count();
        $overdueResolutionReports = Report::query()
            ->whereNotNull('resolution_due_at')
            ->where('resolution_due_at', '<', $today)
            ->whereNotIn('status', [ReportStatus::COMPLETED, ReportStatus::REJECTED])
            ->count();
        $activeRwsWithoutActiveRts = Rw::query()
            ->where('is_active', true)
            ->whereDoesntHave('rts', fn (Builder $query): Builder => $query->where('is_active', true))
            ->count();
        $activeRtsWithoutActiveOfficers = Rt::query()
            ->where('is_active', true)
            ->whereDoesntHave('users', fn (Builder $query): Builder => $query
                ->where('role', UserRole::RT)
                ->where('is_active', true))
            ->count();
        $regionSummary = Rw::query()
            ->where('is_active', true)
            ->withCount([
                'rts as active_rts_count' => fn (Builder $query): Builder => $query->where('is_active', true),
                'reports',
            ])
            ->orderBy('code')
            ->limit(5)
            ->get(['id', 'code', 'name']);

        $selectedRwId = (int) $request->query('rw_id');
        $selectedRtId = (int) $request->query('rt_id');

        $reports = Report::query()
            ->with(['citizen:id,name', 'rt:id,rw_id,code,name', 'rt.rw:id,code,name'])
            ->when(
                $rws->contains('id', $selectedRwId),
                fn (Builder $query): Builder => $query->whereHas(
                    'rt',
                    fn (Builder $query): Builder => $query->where('rw_id', $selectedRwId),
                ),
            )
            ->when(
                $rts->contains('id', $selectedRtId),
                fn (Builder $query): Builder => $query->where('rt_id', $selectedRtId),
            )
            ->when(
                ReportStatus::tryFrom((string) $request->query('status')),
                fn (Builder $query, ReportStatus $status): Builder => $query->where('status', $status),
            )
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim((string) $request->query('search'));

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhereHas(
                            'citizen',
                            fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"),
                        );
                });
            })
            ->latest('reported_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('kelurahan.dashboard', [
            'analytics' => $analyticsService->village(),
            'rws' => $rws,
            'rts' => $rts,
            'reports' => $reports,
            'total' => (int) $counts->sum(),
            'totalRw' => $rws->count(),
            'activeRw' => $rws->where('is_active', true)->count(),
            'totalRt' => $rts->count(),
            'activeRt' => $rts->where('is_active', true)->count(),
            'totalsByStatus' => collect(ReportStatus::cases())->mapWithKeys(
                fn (ReportStatus $status): array => [
                    $status->value => (int) $counts->get($status->value, 0),
                ],
            ),
            'totalsByRw' => $rws->mapWithKeys(
                fn (Rw $rw): array => [$rw->id => (int) $countsByRw->get($rw->id, 0)],
            ),
            'totalsByRt' => $rts->mapWithKeys(
                fn (Rt $rt): array => [$rt->id => (int) $countsByRt->get($rt->id, 0)],
            ),
            'todaySummary' => [
                'created' => $todayCreatedReports,
                'new' => (int) $counts->get(ReportStatus::NEW->value, 0),
                'processing' => (int) $counts->get(ReportStatus::PROCESSING->value, 0),
                'completed' => $todayCompletedReports,
                'active_rws' => $rws->where('is_active', true)->count(),
                'active_rts' => $rts->where('is_active', true)->count(),
            ],
            'totalCitizens' => Citizen::query()->count(),
            'activeCitizenCount' => Citizen::query()->where('is_active', true)->count(),
            'activeFamilyCardCount' => FamilyCard::query()->where('is_active', true)->count(),
            'letterCount' => VillageLetter::query()->count(),
            'attentionSummary' => [
                'new' => (int) $counts->get(ReportStatus::NEW->value, 0),
                'stale_processing' => $staleProcessingReports,
                'overdue_response' => $overdueResponseReports,
                'overdue_resolution' => $overdueResolutionReports,
                'rws_without_active_rts' => $activeRwsWithoutActiveRts,
                'rts_without_active_officers' => $activeRtsWithoutActiveOfficers,
            ],
            'regionSummary' => $regionSummary,
            'posyanduMonthlyVisitCount' => config('modules.posyandu.enabled') === true
                ? PosyanduVisit::query()
                    ->whereBetween('visited_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count()
                : 0,
        ]);
    }

    public function show(Report $report, ReportStatusService $statusService): View
    {
        Gate::authorize('viewForKelurahan', $report);

        $report->load([
            'citizen:id,name',
            'rt:id,rw_id,code,name',
            'rt.rw:id,code,name',
            'attachments',
            'entryRt:id,rw_id,code,name',
            'incidentRt:id,rw_id,code,name',
            'currentRt:id,rw_id,code,name',
            'currentRw:id,code,name',
            'dispositions.forwardedBy:id,name',
            'dispositions.acknowledgedBy:id,name',
        ]);

        $canManage = Gate::allows('manage', $report);
        $canAcknowledge = Gate::allows('acknowledge', $report);

        return view('kelurahan.reports.show', [
            'report' => $report,
            'histories' => $report->histories()
                ->oldest('created_at')
                ->oldest('id')
                ->get(),
            'allowedTransitions' => $canManage
                ? $statusService->allowedTransitions($report->status)
                : [],
            'canAcknowledge' => $canAcknowledge,
        ]);
    }

    public function acknowledge(
        AcknowledgeReportRequest $request,
        Report $report,
        ReportWorkflowService $workflow,
    ): RedirectResponse {
        try {
            $workflow->acknowledge($report, $request->user());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['workflow' => $exception->getMessage()]);
        }

        return redirect()->route('kelurahan.reports.show', $report)
            ->with('status', 'Disposisi laporan telah diterima kelurahan.');
    }

    public function updateStatus(
        UpdateHandledReportStatusRequest $request,
        Report $report,
        ReportStatusService $statusService,
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $statusService->transition(
                $report,
                ReportStatus::from($validated['status']),
                $request->user(),
                $validated['note'] ?? null,
                $validated['public_note'] ?? null,
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }

        return redirect()->route('kelurahan.reports.show', $report)
            ->with('status', 'Status laporan berhasil diperbarui.');
    }
}
