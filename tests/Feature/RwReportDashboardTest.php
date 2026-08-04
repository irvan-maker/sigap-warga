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

class RwReportDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_cannot_access_rw_dashboard(): void
    {
        $this->get(route('rw.dashboard'))->assertRedirect(route('login'));
    }

    public function test_rw_user_can_access_its_dashboard(): void
    {
        [, $user] = $this->createRwUser();

        $this->actingAs($user)
            ->get(route('rw.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard RW');
    }

    public function test_non_rw_user_receives_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('rw.dashboard'))
            ->assertForbidden();
    }

    public function test_rw_sees_only_reports_from_rts_in_its_rw(): void
    {
        [$rw, $user] = $this->createRwUser();
        $ownRt = $this->createRt($rw, '001');
        $otherRw = $this->createRw('002');
        $otherRt = $this->createRt($otherRw, '001');
        $ownReport = $this->createReport($ownRt, ['title' => 'Laporan RW Sendiri']);
        $otherReport = $this->createReport($otherRt, ['title' => 'Laporan RW Lain']);

        $this->actingAs($user)
            ->get(route('rw.dashboard'))
            ->assertOk()
            ->assertSee($ownReport->title)
            ->assertDontSee($otherReport->title);
    }

    public function test_rw_cannot_open_another_rw_report(): void
    {
        [, $user] = $this->createRwUser();
        $otherRw = $this->createRw('002');
        $report = $this->createReport($this->createRt($otherRw));

        $this->actingAs($user)
            ->get(route('rw.reports.show', $report))
            ->assertForbidden();
    }

    public function test_totals_are_correct(): void
    {
        [$rw, $user] = $this->createRwUser();
        $rt = $this->createRt($rw);
        $this->createReport($rt);
        $processing = $this->transition($this->createReport($rt), ReportStatus::PROCESSING);
        $this->transition($processing, ReportStatus::COMPLETED);
        $this->transition($this->createReport($rt), ReportStatus::REJECTED);

        $this->actingAs($user)
            ->get(route('rw.dashboard'))
            ->assertOk()
            ->assertViewHas('total', 3)
            ->assertViewHas('activeRtCount', 1)
            ->assertViewHas('totalsByStatus', fn ($totals): bool =>
                $totals[ReportStatus::NEW->value] === 1
                && $totals[ReportStatus::PROCESSING->value] === 0
                && $totals[ReportStatus::COMPLETED->value] === 1
                && $totals[ReportStatus::REJECTED->value] === 1
            );
    }

    public function test_reports_grouped_by_rt_are_correct(): void
    {
        [$rw, $user] = $this->createRwUser();
        $firstRt = $this->createRt($rw, '001');
        $secondRt = $this->createRt($rw, '002');
        $this->createReport($firstRt);
        $this->createReport($firstRt);
        $this->createReport($secondRt);

        $this->actingAs($user)
            ->get(route('rw.dashboard'))
            ->assertViewHas('totalsByRt', fn ($totals): bool =>
                $totals[$firstRt->id] === 2 && $totals[$secondRt->id] === 1
            );
    }

    public function test_rt_filter_works(): void
    {
        [$rw, $user] = $this->createRwUser();
        $firstRt = $this->createRt($rw, '001');
        $secondRt = $this->createRt($rw, '002');
        $firstReport = $this->createReport($firstRt, ['title' => 'Laporan RT Pertama']);
        $secondReport = $this->createReport($secondRt, ['title' => 'Laporan RT Kedua']);

        $this->actingAs($user)
            ->get(route('rw.dashboard', ['rt_id' => $firstRt->id]))
            ->assertOk()
            ->assertSee($firstReport->title)
            ->assertDontSee($secondReport->title);
    }

    public function test_status_filter_works(): void
    {
        [$rw, $user] = $this->createRwUser();
        $rt = $this->createRt($rw);
        $newReport = $this->createReport($rt, ['title' => 'Status Baru Saja']);
        $processingReport = $this->transition(
            $this->createReport($rt, ['title' => 'Status Proses Saja']),
            ReportStatus::PROCESSING,
        );

        $this->actingAs($user)
            ->get(route('rw.dashboard', ['status' => ReportStatus::PROCESSING->value]))
            ->assertSee($processingReport->title)
            ->assertDontSee($newReport->title);
    }

    public function test_search_works(): void
    {
        [$rw, $user] = $this->createRwUser();
        $rt = $this->createRt($rw);
        $ticketReport = $this->createReport($rt, ['title' => 'Judul Pertama']);
        $citizenReport = $this->createReport($rt, ['title' => 'Judul Kedua']);
        $citizenReport->citizen->update(['name' => 'Warga RW Unik']);
        $titleReport = $this->createReport($rt, ['title' => 'Saluran RW Unik']);

        $this->actingAs($user)
            ->get(route('rw.dashboard', ['search' => $ticketReport->ticket_number]))
            ->assertSee($ticketReport->title)
            ->assertDontSee($citizenReport->title);

        $this->actingAs($user)
            ->get(route('rw.dashboard', ['search' => 'Warga RW Unik']))
            ->assertSee($citizenReport->title)
            ->assertDontSee($ticketReport->title);

        $this->actingAs($user)
            ->get(route('rw.dashboard', ['search' => 'Saluran RW Unik']))
            ->assertSee($titleReport->title)
            ->assertDontSee($ticketReport->title);
    }

    public function test_pagination_works(): void
    {
        [$rw, $user] = $this->createRwUser();
        $rt = $this->createRt($rw);

        foreach (range(1, 11) as $number) {
            $this->createReport($rt, ['title' => "Laporan RW {$number}"]);
        }

        $this->actingAs($user)
            ->get(route('rw.dashboard'))
            ->assertViewHas('reports', fn ($reports): bool => $reports->count() === 10
                && $reports->total() === 11
                && $reports->lastPage() === 2);
    }

    public function test_report_detail_is_read_only(): void
    {
        [$rw, $user] = $this->createRwUser();
        $report = $this->createReport($this->createRt($rw), [
            'title' => 'Detail Monitoring RW',
            'description' => 'Deskripsi hanya untuk monitoring.',
        ]);

        $this->actingAs($user)
            ->get(route('rw.reports.show', $report))
            ->assertOk()
            ->assertSee($report->ticket_number)
            ->assertSee($report->title)
            ->assertSee($report->description)
            ->assertSee('Riwayat Status')
            ->assertDontSee('Ubah Status')
            ->assertDontSee('Simpan Status');
    }

    public function test_no_status_update_route_is_available_to_rw(): void
    {
        [$rw, $user] = $this->createRwUser();
        $report = $this->createReport($this->createRt($rw));

        $this->actingAs($user)
            ->patch("/rw/reports/{$report->id}/status", [
                'status' => ReportStatus::PROCESSING->value,
            ])->assertNotFound();

        $this->assertSame(ReportStatus::NEW, $report->fresh()->status);
    }

    /**
     * @return array{Rw, User}
     */
    private function createRwUser(): array
    {
        $rw = $this->createRw();
        $user = User::factory()->create([
            'role' => UserRole::RW,
            'rw_id' => $rw->id,
            'rt_id' => null,
        ]);

        return [$rw, $user];
    }

    /**
     * @param array<string, mixed> $attributes
     */
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

    private function createRw(string $code = '001'): Rw
    {
        return Rw::query()->create(['code' => $code, 'name' => "RW {$code}"]);
    }

    private function createRt(Rw $rw, string $code = '001'): Rt
    {
        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => $code,
            'name' => "RT {$code}",
        ]);
    }
}
