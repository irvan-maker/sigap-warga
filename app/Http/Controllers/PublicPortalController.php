<?php

namespace App\Http\Controllers;

use App\Enums\LetterStatus;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\VillageLetter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PublicPortalController extends Controller
{
    public function __invoke(): View
    {
        $counts = Report::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $total = (int) $counts->sum();
        $completed = (int) ($counts[ReportStatus::COMPLETED->value] ?? 0);
        $monthSql = DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
        $from = now()->startOfMonth()->subMonths(5);
        $reports = Report::query()->where('created_at', '>=', $from)->selectRaw("{$monthSql} as month, COUNT(*) as aggregate")->groupBy('month')->pluck('aggregate', 'month');
        $letters = VillageLetter::query()->where('created_at', '>=', $from)->selectRaw("{$monthSql} as month, COUNT(*) as aggregate")->groupBy('month')->pluck('aggregate', 'month');
        $trend = collect(range(0, 5))->map(function (int $offset) use ($from, $reports, $letters): array {
            $date = Carbon::instance($from)->copy()->addMonths($offset);
            $key = $date->format('Y-m');

            return ['label' => $date->translatedFormat('M Y'), 'reports' => (int) ($reports[$key] ?? 0), 'letters' => (int) ($letters[$key] ?? 0)];
        });

        return view('public.home', [
            'statistics' => [
                'total_reports' => $total,
                'completed_reports' => $completed,
                'processing_reports' => (int) ($counts[ReportStatus::PROCESSING->value] ?? 0),
                'issued_letters' => VillageLetter::query()->where('status', LetterStatus::ISSUED)->count(),
                'completion_percentage' => $total > 0 ? round($completed / $total * 100, 1) : 0,
            ],
            'trend' => $trend,
        ]);
    }
}
