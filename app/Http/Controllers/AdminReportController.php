<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Http\Requests\IndexAdminReportsRequest;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(IndexAdminReportsRequest $request): View
    {
        $filters = $request->validated();

        $reports = Report::query()
            ->with([
                'citizen:id,name',
                'rt:id,rw_id,code,name',
                'rt.rw:id,code,name',
            ])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
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
            ->when(
                isset($filters['status']),
                fn (Builder $query): Builder => $query->where(
                    'status',
                    ReportStatus::from($filters['status']),
                ),
            )
            ->when(
                isset($filters['rw_id']),
                fn (Builder $query): Builder => $query->whereHas(
                    'rt',
                    fn (Builder $query): Builder => $query->where('rw_id', $filters['rw_id']),
                ),
            )
            ->when(
                isset($filters['rt_id']),
                fn (Builder $query): Builder => $query->where('rt_id', $filters['rt_id']),
            )
            ->when(
                isset($filters['date_from']),
                fn (Builder $query): Builder => $query->where(
                    'reported_at',
                    '>=',
                    Carbon::createFromFormat('Y-m-d', $filters['date_from'])->startOfDay(),
                ),
            )
            ->when(
                isset($filters['date_to']),
                fn (Builder $query): Builder => $query->where(
                    'reported_at',
                    '<=',
                    Carbon::createFromFormat('Y-m-d', $filters['date_to'])->endOfDay(),
                ),
            )
            ->latest('reported_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'rws' => Rw::query()->orderBy('code')->get(['id', 'code', 'name']),
            'rts' => Rt::query()
                ->with('rw:id,code')
                ->orderBy('rw_id')
                ->orderBy('code')
                ->get(['id', 'rw_id', 'code', 'name']),
        ]);
    }
}
