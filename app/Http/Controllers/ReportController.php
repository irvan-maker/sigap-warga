<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreManualReportRequest;
use App\Models\Report;
use App\Models\Rt;
use App\Services\CreateManualReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Report::class);

        return view('reports.create', [
            'rts' => Rt::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function store(
        StoreManualReportRequest $request,
        CreateManualReportService $service,
    ): RedirectResponse {
        $report = $service->create($request->validated());

        return redirect()->route('reports.show', $report);
    }

    public function show(Report $report): View
    {
        Gate::authorize('view', $report);

        $report->load(['citizen', 'rt', 'attachments']);

        return view('reports.show', [
            'report' => $report,
            'histories' => $report->histories()
                ->oldest('created_at')
                ->oldest('id')
                ->get(),
        ]);
    }
}
