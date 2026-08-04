<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\Rt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RwReportController extends Controller
{
    public function index(Request $request): View
    {
        $rwId = $request->user()->rw_id;
        $rts = Rt::query()
            ->where('rw_id', $rwId)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'is_active']);
        $rtIds = $rts->pluck('id');

        $counts = Report::query()
            ->whereIn('rt_id', $rtIds)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $countsByRt = Report::query()
            ->whereIn('rt_id', $rtIds)
            ->selectRaw('rt_id, COUNT(*) as aggregate')
            ->groupBy('rt_id')
            ->pluck('aggregate', 'rt_id');

        $selectedRtId = (int) $request->query('rt_id');

        $reports = Report::query()
            ->with(['citizen:id,name', 'rt:id,code,name'])
            ->whereIn('rt_id', $rtIds)
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
        ]);
    }

    public function show(Report $report): View
    {
        Gate::authorize('viewForRw', $report);

        $report->load(['citizen:id,name', 'rt:id,code,name']);

        return view('rw.reports.show', [
            'report' => $report,
            'histories' => $report->histories()
                ->oldest('created_at')
                ->oldest('id')
                ->get(),
        ]);
    }
}
