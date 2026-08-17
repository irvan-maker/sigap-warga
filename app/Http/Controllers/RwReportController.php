<?php

namespace App\Http\Controllers;

use App\Enums\ReportHandlingLevel;
use App\Enums\ReportStatus;
use App\Http\Requests\AcknowledgeReportRequest;
use App\Http\Requests\ForwardReportRequest;
use App\Http\Requests\UpdateHandledReportStatusRequest;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\PosyanduVisit;
use App\Models\Report;
use App\Models\Rt;
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

class RwReportController extends Controller
{
    public function index(Request $request, VillageAnalyticsService $analyticsService): View
    {
        $rwId = $request->user()->rw_id;
        $rts = Rt::query()
            ->where('rw_id', $rwId)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'is_active']);
        $rtIds = $rts->pluck('id');

        $counts = Report::query()
            ->visibleToRw($rwId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $countsByRt = Report::query()
            ->visibleToRw($rwId)
            ->selectRaw('rt_id, COUNT(*) as aggregate')
            ->groupBy('rt_id')
            ->pluck('aggregate', 'rt_id');

        $selectedRtId = (int) $request->query('rt_id');

        $reports = Report::query()
            ->with(['citizen:id,name', 'rt:id,code,name'])
            ->visibleToRw($rwId)
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

        return view('rw.dashboard', [
            'analytics' => $analyticsService->rw($rwId),
            'rts' => $rts,
            'reports' => $reports,
            'activeRtCount' => $rts->where('is_active', true)->count(),
            'total' => (int) $counts->sum(),
            'totalsByStatus' => collect(ReportStatus::cases())->mapWithKeys(
                fn (ReportStatus $status): array => [
                    $status->value => (int) $counts->get($status->value, 0),
                ],
            ),
            'totalsByRt' => $rts->mapWithKeys(
                fn (Rt $rt): array => [$rt->id => (int) $countsByRt->get($rt->id, 0)],
            ),
            'activeCitizenCount' => Citizen::query()->whereIn('rt_id', $rtIds)->where('is_active', true)->count(),
            'activeFamilyCardCount' => FamilyCard::query()->whereIn('rt_id', $rtIds)->where('is_active', true)->count(),
            'letterCount' => VillageLetter::query()->whereIn('rt_id', $rtIds)->count(),
            'posyanduMonthlyVisitCount' => config('modules.posyandu.enabled') === true
                ? PosyanduVisit::query()
                    ->whereHas('site.rt', fn (Builder $rt): Builder => $rt->where('rw_id', $rwId))
                    ->whereBetween('visited_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count()
                : 0,
        ]);
    }

    public function show(Report $report, ReportStatusService $statusService): View
    {
        Gate::authorize('viewForRw', $report);

        $report->load([
            'citizen:id,name',
            'rt:id,rw_id,code,name',
            'rt.rw:id,code,name',
            'entryRt:id,rw_id,code,name',
            'incidentRt:id,rw_id,code,name',
            'currentRt:id,rw_id,code,name',
            'currentRw:id,code,name',
            'attachments',
            'dispositions.forwardedBy:id,name',
            'dispositions.acknowledgedBy:id,name',
        ]);

        $canManage = Gate::allows('manage', $report);
        $canAcknowledge = Gate::allows('acknowledge', $report);
        $canForward = Gate::allows('forward', $report);

        return view('rw.reports.show', [
            'report' => $report,
            'histories' => $report->histories()
                ->oldest('created_at')
                ->oldest('id')
                ->get(),
            'allowedTransitions' => $canManage
                ? $statusService->allowedTransitions($report->status)
                : [],
            'canAcknowledge' => $canAcknowledge,
            'canForward' => $canForward,
            'targetRts' => Rt::query()
                ->where('rw_id', request()->user()->rw_id)
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function acknowledge(
        AcknowledgeReportRequest $request,
        Report $report,
        ReportWorkflowService $workflow,
    ): RedirectResponse {
        $this->workflowAction(fn () => $workflow->acknowledge($report, $request->user()));

        return redirect()->route('rw.reports.show', $report)
            ->with('status', 'Disposisi laporan telah diterima.');
    }

    public function forward(
        ForwardReportRequest $request,
        Report $report,
        ReportWorkflowService $workflow,
    ): RedirectResponse {
        $validated = $request->validated();
        $publicUpdate = $request->validate([
            'public_note' => ['required', 'string', 'max:2000'],
        ])['public_note'];
        $targetRt = isset($validated['target_rt_id'])
            ? Rt::query()->find($validated['target_rt_id'])
            : null;

        $this->workflowAction(fn () => $workflow->forward(
            $report,
            $request->user(),
            ReportHandlingLevel::from($validated['target_level']),
            $validated['reason'],
            $targetRt,
            $publicUpdate,
        ));

        return redirect()->route('rw.reports.show', $report)
            ->with('status', 'Laporan berhasil diteruskan.');
    }

    public function updateStatus(
        UpdateHandledReportStatusRequest $request,
        Report $report,
        ReportStatusService $statusService,
    ): RedirectResponse {
        $validated = $request->validated();
        $this->workflowAction(fn () => $statusService->transition(
            $report,
            ReportStatus::from($validated['status']),
            $request->user(),
            $validated['note'] ?? null,
            $validated['public_note'] ?? null,
        ));

        return redirect()->route('rw.reports.show', $report)
            ->with('status', 'Status laporan berhasil diperbarui.');
    }

    private function workflowAction(callable $action): void
    {
        try {
            $action();
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['workflow' => $exception->getMessage()]);
        }
    }
}
