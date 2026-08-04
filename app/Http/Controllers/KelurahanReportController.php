<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KelurahanReportController extends Controller
{
    public function index(Request $request): View
    {
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
        ]);
    }

    public function show(Report $report): View
    {
        Gate::authorize('viewForKelurahan', $report);

        $report->load(['citizen:id,name', 'rt:id,rw_id,code,name', 'rt.rw:id,code,name']);

        return view('kelurahan.reports.show', [
            'report' => $report,
            'histories' => $report->histories()
                ->oldest('created_at')
                ->oldest('id')
                ->get(),
        ]);
    }
}
