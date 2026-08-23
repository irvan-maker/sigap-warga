<?php

namespace App\Services;

use App\Enums\LetterStatus;
use App\Enums\LetterType;
use App\Enums\ReportStatus;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Report;
use App\Models\Rt;
use App\Models\VillageLetter;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VillageAnalyticsService
{
    /** @return array<string, mixed> */
    public function village(): array
    {
        $rts = Rt::query()->with('rw:id,code,name')->orderBy('rw_id')->orderBy('code')->get(['id', 'rw_id', 'code', 'name']);
        $rtIds = $rts->pluck('id');
        $reportsByRt = $this->countsByRt(Report::query());
        $lettersByRt = $this->countsByRt(VillageLetter::query());
        $citizensByRt = Citizen::query()->selectRaw('rt_id, COUNT(*) as aggregate')->groupBy('rt_id')->pluck('aggregate', 'rt_id');

        return [
            'kpis' => [
                'citizens' => Citizen::query()->count(),
                'family_cards' => FamilyCard::query()->count(),
                'reports' => Report::query()->count(),
                'letters' => VillageLetter::query()->count(),
            ],
            'monthly_reports' => $this->monthlySeries(Report::query(), 'reported_at'),
            'monthly_letters' => $this->monthlySeries(VillageLetter::query(), 'created_at'),
            'report_statuses' => $this->statusSeries(Report::query(), ReportStatus::cases()),
            'letter_statuses' => $this->statusSeries(VillageLetter::query(), LetterStatus::cases()),
            'reports_by_rw' => $this->byRwSeries('reports'),
            'letters_by_rw' => $this->byRwSeries('village_letters'),
            'rankings' => $this->rankings($rts, $reportsByRt, $lettersByRt, $citizensByRt),
            'insights' => $this->villageInsights($rts, $reportsByRt),
        ];
    }

    /** @return array<string, mixed> */
    public function rw(int $rwId): array
    {
        $rts = Rt::query()->where('rw_id', $rwId)->orderBy('code')->get(['id', 'rw_id', 'code', 'name']);
        $rtIds = $rts->pluck('id');
        $reports = Report::query()->whereIn('rt_id', $rtIds);
        $letters = VillageLetter::query()->whereIn('rt_id', $rtIds);
        $reportsByRt = $this->countsByRt(clone $reports);
        $lettersByRt = $this->countsByRt(clone $letters);
        $citizensByRt = Citizen::query()->whereIn('rt_id', $rtIds)->selectRaw('rt_id, COUNT(*) as aggregate')->groupBy('rt_id')->pluck('aggregate', 'rt_id');

        return [
            'kpis' => [
                'rts' => $rts->count(),
                'citizens' => (int) $citizensByRt->sum(),
                'family_cards' => FamilyCard::query()->whereIn('rt_id', $rtIds)->count(),
                'letters' => (clone $letters)->count(),
                'reports' => (clone $reports)->count(),
            ],
            'reports_by_rt' => $this->rtSeries($rts, $reportsByRt),
            'letters_by_rt' => $this->rtSeries($rts, $lettersByRt),
            'rankings' => $this->rankings($rts, $reportsByRt, $lettersByRt, $citizensByRt),
            'insights' => $this->scopedInsights($rtIds, $rts, $reportsByRt),
        ];
    }

    /** @return array<string, mixed> */
    public function rt(int $rtId): array
    {
        $demographics = Citizen::query()->where('rt_id', $rtId)->selectRaw(
            "SUM(CASE WHEN gender = 'L' THEN 1 ELSE 0 END) as male,
             SUM(CASE WHEN gender = 'P' THEN 1 ELSE 0 END) as female,
             SUM(CASE WHEN family_card_id IS NULL THEN 1 ELSE 0 END) as without_family_card,
             SUM(CASE WHEN nik IS NULL OR nik = '' THEN 1 ELSE 0 END) as without_nik",
        )->first();

        $today = now()->startOfDay();
        $ageCounts = Citizen::query()->where('rt_id', $rtId)->selectRaw(
            'SUM(CASE WHEN birth_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as age_0_5,
             SUM(CASE WHEN birth_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as age_6_17,
             SUM(CASE WHEN birth_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as age_18_40,
             SUM(CASE WHEN birth_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as age_41_60,
             SUM(CASE WHEN birth_date < ? THEN 1 ELSE 0 END) as age_60_plus',
            [
                $today->copy()->subYears(6)->addDay(), $today,
                $today->copy()->subYears(18)->addDay(), $today->copy()->subYears(6),
                $today->copy()->subYears(41)->addDay(), $today->copy()->subYears(18),
                $today->copy()->subYears(61)->addDay(), $today->copy()->subYears(41),
                $today->copy()->subYears(61)->addDay(),
            ],
        )->first();

        $withoutHead = FamilyCard::query()->where('rt_id', $rtId)->whereNull('head_citizen_id')->count();

        return [
            'gender' => ['male' => (int) $demographics->male, 'female' => (int) $demographics->female],
            'ages' => [
                'labels' => ['0–5', '6–17', '18–40', '41–60', '60+'],
                'data' => [(int) $ageCounts->age_0_5, (int) $ageCounts->age_6_17, (int) $ageCounts->age_18_40, (int) $ageCounts->age_41_60, (int) $ageCounts->age_60_plus],
            ],
            'data_quality' => [
                'without_family_card' => (int) $demographics->without_family_card,
                'without_nik' => (int) $demographics->without_nik,
                'family_cards_without_head' => $withoutHead,
            ],
            'insights' => $this->rtInsights((int) $demographics->without_nik, (int) $demographics->without_family_card, $withoutHead),
        ];
    }

    /** @param Builder<Report>|Builder<VillageLetter> $query */
    private function countsByRt(Builder $query): Collection
    {
        return $query->selectRaw('rt_id, COUNT(*) as aggregate')->groupBy('rt_id')->pluck('aggregate', 'rt_id');
    }

    /** @param Builder<Report>|Builder<VillageLetter> $query */
    private function monthlySeries(Builder $query, string $column): array
    {
        $months = collect(range(11, 0))->map(fn (int $ago) => now()->startOfMonth()->subMonths($ago));
        $expression = $this->monthExpression($column);
        $counts = $query->where($column, '>=', $months->first())->where($column, '<', now()->startOfMonth()->addMonth())
            ->selectRaw("{$expression} as period, COUNT(*) as aggregate")->groupByRaw($expression)->pluck('aggregate', 'period');

        return [
            'labels' => $months->map(fn (CarbonInterface $month) => $month->locale('id')->isoFormat('MMM YY'))->values(),
            'data' => $months->map(fn (CarbonInterface $month) => (int) $counts->get($month->format('Y-m'), 0))->values(),
        ];
    }

    /** @param Builder<Report>|Builder<VillageLetter> $query */
    private function statusSeries(Builder $query, array $statuses): array
    {
        $counts = $query->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return [
            'labels' => collect($statuses)->map(fn ($status) => $status->label())->values(),
            'data' => collect($statuses)->map(fn ($status) => (int) $counts->get($status->value, 0))->values(),
        ];
    }

    private function byRwSeries(string $table): array
    {
        $rows = DB::table('rws')->leftJoin('rts', 'rts.rw_id', '=', 'rws.id')->leftJoin($table, "{$table}.rt_id", '=', 'rts.id')
            ->selectRaw("rws.id, rws.code, COUNT({$table}.id) as aggregate")->groupBy('rws.id', 'rws.code')->orderBy('rws.code')->get();

        return ['labels' => $rows->map(fn ($row) => "RW {$row->code}"), 'data' => $rows->map(fn ($row) => (int) $row->aggregate)];
    }

    private function rtSeries(Collection $rts, Collection $counts): array
    {
        return ['labels' => $rts->map(fn (Rt $rt) => "RT {$rt->code}")->values(), 'data' => $rts->map(fn (Rt $rt) => (int) $counts->get($rt->id, 0))->values()];
    }

    private function rankings(Collection $rts, Collection $reports, Collection $letters, Collection $citizens): Collection
    {
        return $rts->map(fn (Rt $rt) => [
            'id' => $rt->id,
            'label' => ($rt->relationLoaded('rw') ? "RW {$rt->rw->code} / " : '')."RT {$rt->code}",
            'reports' => (int) $reports->get($rt->id, 0),
            'letters' => (int) $letters->get($rt->id, 0),
            'citizens' => (int) $citizens->get($rt->id, 0),
            'activity' => (int) $reports->get($rt->id, 0) + (int) $letters->get($rt->id, 0),
        ])->values();
    }

    private function villageInsights(Collection $rts, Collection $reportsByRt): array
    {
        return $this->scopedInsights($rts->pluck('id'), $rts, $reportsByRt);
    }

    private function scopedInsights(Collection $rtIds, Collection $rts, Collection $reportsByRt): array
    {
        $currentStart = now()->startOfMonth();
        $previousStart = $currentStart->copy()->subMonth();
        $monthly = Report::query()->whereIn('rt_id', $rtIds)->where('reported_at', '>=', $previousStart)
            ->selectRaw('rt_id, SUM(CASE WHEN reported_at >= ? THEN 1 ELSE 0 END) as current_count, SUM(CASE WHEN reported_at < ? THEN 1 ELSE 0 END) as previous_count', [$currentStart, $currentStart])
            ->groupBy('rt_id')->get()->keyBy('rt_id');
        $growth = $monthly->filter(fn ($row) => (int) $row->previous_count > 0)->map(fn ($row) => (int) round((((int) $row->current_count - (int) $row->previous_count) / (int) $row->previous_count) * 100))->sortDesc();
        $growthRt = $rts->firstWhere('id', $growth->keys()->first());
        $topLetter = VillageLetter::query()->whereIn('rt_id', $rtIds)->where('created_at', '>=', $currentStart)->whereNotNull('letter_type')->selectRaw('letter_type, COUNT(*) as aggregate')->groupBy('letter_type')->orderByDesc('aggregate')->first();
        $topRt = $rts->sortByDesc(fn (Rt $rt) => (int) $reportsByRt->get($rt->id, 0))->first();

        return array_values(array_filter([
            $growthRt ? "RT {$growthRt->code} mengalami perubahan laporan {$growth->first()}% dibanding bulan lalu." : null,
            $topLetter ? 'Jenis surat terbanyak bulan ini adalah '.($topLetter->letter_type instanceof LetterType ? $topLetter->letter_type : LetterType::from($topLetter->letter_type))->label()." ({$topLetter->aggregate} surat)." : 'Belum ada pengajuan surat bulan ini.',
            $topRt ? "RT {$topRt->code} memiliki laporan terbanyak dengan ".(int) $reportsByRt->get($topRt->id, 0).' laporan.' : null,
        ]));
    }

    private function rtInsights(int $withoutNik, int $withoutFamilyCard, int $withoutHead): array
    {
        return [
            "{$withoutNik} warga belum memiliki NIK.",
            "{$withoutFamilyCard} warga belum terhubung dengan KK.",
            "{$withoutHead} KK belum memiliki kepala keluarga.",
        ];
    }

    private function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            'sqlsrv' => "FORMAT({$column}, 'yyyy-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
