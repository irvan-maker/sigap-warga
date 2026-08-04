<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Services\ReportStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelurahanReportDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_cannot_access(): void
    {
        $this->get(route('kelurahan.dashboard'))->assertForbidden();
    }

    public function test_rt_cannot_access(): void
    {
        $rw = $this->createRw();
        $rt = $this->createRt($rw);
        $user = User::factory()->create([
            'role' => UserRole::RT,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
        ]);

        $this->actingAs($user)->get(route('kelurahan.dashboard'))->assertForbidden();
    }

    public function test_rw_cannot_access(): void
    {
        $rw = $this->createRw();
        $user = User::factory()->create([
            'role' => UserRole::RW,
            'rw_id' => $rw->id,
            'rt_id' => null,
        ]);

        $this->actingAs($user)->get(route('kelurahan.dashboard'))->assertForbidden();
    }

    public function test_admin_cannot_access(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('kelurahan.dashboard'))
            ->assertForbidden();
    }

    public function test_kelurahan_can_access(): void
    {
        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Kelurahan');
    }

    public function test_totals_are_correct(): void
    {
        $activeRw = $this->createRw('001');
        $inactiveRw = $this->createRw('002', false);
        $activeRt = $this->createRt($activeRw, '001');
        $this->createRt($inactiveRw, '001', false);
        $this->createReport($activeRt);
        $processing = $this->transition($this->createReport($activeRt), ReportStatus::PROCESSING);
        $this->transition($processing, ReportStatus::COMPLETED);
        $this->transition($this->createReport($activeRt), ReportStatus::REJECTED);

        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard'))
            ->assertViewHas('total', 3)
            ->assertViewHas('totalRw', 2)
            ->assertViewHas('activeRw', 1)
            ->assertViewHas('totalRt', 2)
            ->assertViewHas('activeRt', 1)
            ->assertViewHas('totalsByStatus', fn ($totals): bool =>
                $totals[ReportStatus::NEW->value] === 1
                && $totals[ReportStatus::PROCESSING->value] === 0
                && $totals[ReportStatus::COMPLETED->value] === 1
                && $totals[ReportStatus::REJECTED->value] === 1
            );
    }

    public function test_rw_grouping_is_correct(): void
    {
        $firstRw = $this->createRw('001');
        $secondRw = $this->createRw('002');
        $firstRt = $this->createRt($firstRw);
        $secondRt = $this->createRt($secondRw);
        $this->createReport($firstRt);
        $this->createReport($firstRt);
        $this->createReport($secondRt);

        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard'))
            ->assertViewHas('totalsByRw', fn ($totals): bool =>
                $totals[$firstRw->id] === 2 && $totals[$secondRw->id] === 1
            );
    }

    public function test_rt_grouping_is_correct(): void
    {
        $rw = $this->createRw();
        $firstRt = $this->createRt($rw, '001');
        $secondRt = $this->createRt($rw, '002');
        $this->createReport($firstRt);
        $this->createReport($secondRt);
        $this->createReport($secondRt);

        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard'))
            ->assertViewHas('totalsByRt', fn ($totals): bool =>
                $totals[$firstRt->id] === 1 && $totals[$secondRt->id] === 2
            );
    }

    public function test_rw_filter_works(): void
    {
        $firstRw = $this->createRw('001');
        $secondRw = $this->createRw('002');
        $firstReport = $this->createReport($this->createRt($firstRw), ['title' => 'Laporan RW Pertama']);
        $secondReport = $this->createReport($this->createRt($secondRw), ['title' => 'Laporan RW Kedua']);

        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard', ['rw_id' => $firstRw->id]))
            ->assertSee($firstReport->title)
            ->assertDontSee($secondReport->title);
    }

    public function test_rt_filter_works(): void
    {
        $rw = $this->createRw();
        $firstRt = $this->createRt($rw, '001');
        $secondRt = $this->createRt($rw, '002');
        $firstReport = $this->createReport($firstRt, ['title' => 'Laporan RT Pertama']);
        $secondReport = $this->createReport($secondRt, ['title' => 'Laporan RT Kedua']);

        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard', ['rt_id' => $firstRt->id]))
            ->assertSee($firstReport->title)
            ->assertDontSee($secondReport->title);
    }

    public function test_status_filter_works(): void
    {
        $rw = $this->createRw();
        $rt = $this->createRt($rw);
        $newReport = $this->createReport($rt, ['title' => 'Status Baru Kelurahan']);
        $processingReport = $this->transition(
            $this->createReport($rt, ['title' => 'Status Proses Kelurahan']),
            ReportStatus::PROCESSING,
        );

        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard', ['status' => ReportStatus::PROCESSING->value]))
            ->assertSee($processingReport->title)
            ->assertDontSee($newReport->title);
    }

    public function test_search_works(): void
    {
        $rw = $this->createRw();
        $rt = $this->createRt($rw);
        $ticketReport = $this->createReport($rt, ['title' => 'Judul Pertama']);
        $citizenReport = $this->createReport($rt, ['title' => 'Judul Kedua']);
        $citizenReport->citizen->update(['name' => 'Warga Kelurahan Unik']);
        $titleReport = $this->createReport($rt, ['title' => 'Drainase Kelurahan Unik']);
        $user = $this->createKelurahanUser();

        $this->actingAs($user)
            ->get(route('kelurahan.dashboard', ['search' => $ticketReport->ticket_number]))
            ->assertSee($ticketReport->title)->assertDontSee($citizenReport->title);
        $this->actingAs($user)
            ->get(route('kelurahan.dashboard', ['search' => 'Warga Kelurahan Unik']))
            ->assertSee($citizenReport->title)->assertDontSee($ticketReport->title);
        $this->actingAs($user)
            ->get(route('kelurahan.dashboard', ['search' => 'Drainase Kelurahan Unik']))
            ->assertSee($titleReport->title)->assertDontSee($ticketReport->title);
    }

    public function test_pagination_works(): void
    {
        $rw = $this->createRw();
        $rt = $this->createRt($rw);

        foreach (range(1, 11) as $number) {
            $this->createReport($rt, ['title' => "Laporan Kelurahan {$number}"]);
        }

        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard'))
            ->assertViewHas('reports', fn ($reports): bool => $reports->count() === 10
                && $reports->total() === 11
                && $reports->lastPage() === 2);
    }

    public function test_report_detail_works(): void
    {
        $rw = $this->createRw();
        $rt = $this->createRt($rw);
        $report = $this->createReport($rt, [
            'title' => 'Detail Laporan Kelurahan',
            'description' => 'Deskripsi monitoring Kelurahan.',
        ]);

        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.reports.show', $report))
            ->assertOk()
            ->assertSee($report->ticket_number)
            ->assertSee($report->citizen->name)
            ->assertSee($rw->name)
            ->assertSee($rt->name)
            ->assertSee($report->title)
            ->assertSee($report->description)
            ->assertSee('Riwayat Status');
    }

    public function test_report_detail_is_read_only(): void
    {
        $rw = $this->createRw();
        $report = $this->createReport($this->createRt($rw));

        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.reports.show', $report))
            ->assertOk()
            ->assertDontSee('Ubah Status')
            ->assertDontSee('Simpan Status')
            ->assertDontSee('Hapus Laporan');
    }

    private function createKelurahanUser(): User
    {
        return User::factory()->create(['role' => UserRole::KELURAHAN]);
    }

    /** @param array<string, mixed> $attributes */
    private function createReport(Rt $rt, array $attributes = []): Report
    {
        $citizen = Citizen::factory()->for($rt)->create();

        return Report::factory()->create(array_merge([
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
        ], $attributes));
    }

    private function transition(Report $report, ReportStatus $status): Report
    {
        return app(ReportStatusService::class)->transition($report, $status);
    }

    private function createRw(string $code = '001', bool $isActive = true): Rw
    {
        return Rw::query()->create(['code' => $code, 'name' => "RW {$code}", 'is_active' => $isActive]);
    }

    private function createRt(Rw $rw, string $code = '001', bool $isActive = true): Rt
    {
        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => $code,
            'name' => "RT {$code}",
            'is_active' => $isActive,
        ]);
    }
}
