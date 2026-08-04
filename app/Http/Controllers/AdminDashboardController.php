<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\ReportHistory;
use App\Models\Rt;
use App\Models\Rw;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $redirectRoute = match ($request->user()->role) {
            UserRole::RT => 'rt.dashboard',
            UserRole::RW => 'rw.dashboard',
            UserRole::KELURAHAN => 'kelurahan.dashboard',
            UserRole::ADMIN => null,
        };

        if ($redirectRoute !== null) {
            return redirect()->route($redirectRoute);
        }

        $counts = Report::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $totalReports = (int) $counts->sum();

        return view('dashboard', [
            'totalCitizens' => Citizen::query()->count(),
            'totalActiveRws' => Rw::query()->where('is_active', true)->count(),
            'totalActiveRts' => Rt::query()->where('is_active', true)->count(),
            'totalReports' => $totalReports,
            'totalsByStatus' => collect(ReportStatus::cases())->mapWithKeys(
                fn (ReportStatus $status): array => [
                    $status->value => (int) $counts->get($status->value, 0),
                ],
            ),
            'latestReports' => Report::query()
                ->with([
                    'citizen:id,name',
                    'rt:id,rw_id,code,name',
                    'rt.rw:id,code,name',
                ])
                ->latest('reported_at')
                ->latest('id')
                ->limit(8)
                ->get(),
            'reportSummaryByRt' => Rt::query()
                ->with('rw:id,code,name')
                ->withCount([
                    'reports',
                    'reports as new_reports_count' => fn ($query) => $query->where('status', ReportStatus::NEW),
                    'reports as processing_reports_count' => fn ($query) => $query->where('status', ReportStatus::PROCESSING),
                    'reports as completed_reports_count' => fn ($query) => $query->where('status', ReportStatus::COMPLETED),
                    'reports as rejected_reports_count' => fn ($query) => $query->where('status', ReportStatus::REJECTED),
                ])
                ->orderBy('code')
                ->get(),
            'latestActivities' => ReportHistory::query()
                ->with([
                    'user:id,name',
                    'report:id,ticket_number,citizen_id,rt_id,title',
                    'report.citizen:id,name',
                    'report.rt:id,rw_id,code,name',
                    'report.rt.rw:id,code,name',
                ])
                ->latest('created_at')
                ->latest('id')
                ->limit(8)
                ->get(),
        ]);
    }
}
