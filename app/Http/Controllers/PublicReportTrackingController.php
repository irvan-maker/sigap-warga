<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackingReportRequest;
use App\Services\ReportTrackingService;
use Illuminate\View\View;

class PublicReportTrackingController extends Controller
{
    public function index(): View
    {
        return view('tracking.index', [
            'report' => null,
            'searched' => false,
        ]);
    }

    public function store(
        TrackingReportRequest $request,
        ReportTrackingService $trackingService,
    ): View {
        $validated = $request->validated();

        return view('tracking.index', [
            'report' => $trackingService->find(
                $validated['ticket_number'],
                $validated['phone_normalized'],
            ),
            'searched' => true,
        ]);
    }
}
