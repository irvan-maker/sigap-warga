<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Http\Requests\UpdateRtReportStatusRequest;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Report;
use App\Models\VillageLetter;
use App\Services\ReportStatusService;
use App\Services\VillageAnalyticsService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RtReportController extends Controller
{
    public function index(Request $request, VillageAnalyticsService $analyticsService): View
    {
        $rtId = $request->user()->rt_id;

        $counts = Report::query()
            ->where('rt_id', $rtId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $reports = Report::query()
            ->with(['citizen:id,name', 'rt:id,code,name'])
            ->where('rt_id', $rtId)
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

        return view('rt.dashboard', [
            'analytics' => $analyticsService->rt($rtId),
            'reports' => $reports,
            'total' => (int) $counts->sum(),
            'totalsByStatus' => collect(ReportStatus::cases())->mapWithKeys(
                fn (ReportStatus $status): array => [
                    $status->value => (int) $counts->get($status->value, 0),
                ],
            ),
            'activeCitizenCount' => Citizen::query()->where('rt_id', $rtId)->where('is_active', true)->count(),
            'activeFamilyCardCount' => FamilyCard::query()->where('rt_id', $rtId)->where('is_active', true)->count(),
            'familyCardsWithoutHeadCount' => FamilyCard::query()->where('rt_id', $rtId)->whereNull('head_citizen_id')->count(),
            'citizensWithoutFamilyCardCount' => Citizen::query()
                ->where('rt_id', $rtId)
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('family_card_id')
                    ->orWhere('family_card_id', ''))
                ->count(),
            'citizensWithoutNikCount' => Citizen::query()
                ->where('rt_id', $rtId)
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('nik')
                    ->orWhere('nik', ''))
                ->count(),
            'letterCounts' => VillageLetter::query()->where('rt_id', $rtId)->selectRaw('status, COUNT(*) aggregate')->groupBy('status')->pluck('aggregate', 'status'),
        ]);
    }

    public function show(Report $report, ReportStatusService $statusService): View
    {
        Gate::authorize('viewForRt', $report);

        $report->load(['citizen:id,name', 'rt:id,code,name', 'attachments']);

        return view('rt.reports.show', [
            'report' => $report,
            'histories' => $report->histories()
                ->oldest('created_at')
                ->oldest('id')
                ->get(),
            'allowedTransitions' => $statusService->allowedTransitions($report->status),
        ]);
    }

    public function updateStatus(
        UpdateRtReportStatusRequest $request,
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
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'status' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('rt.reports.show', $report)
            ->with('status', 'Status laporan berhasil diperbarui.');
    }
}
