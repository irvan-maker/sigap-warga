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

class RtReportDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_cannot_access_rt_dashboard(): void
    {
        $this->get(route('rt.dashboard'))->assertRedirect(route('login'));
    }

    public function test_rt_user_can_access_its_dashboard(): void
    {
        [, $user] = $this->createRtUser();

        $this->actingAs($user)
            ->get(route('rt.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard RT');
    }

    public function test_rt_dashboard_does_not_offer_admin_only_report_creation(): void
    {
        [, $user] = $this->createRtUser();

        $this->actingAs($user)
            ->get(route('rt.dashboard'))
            ->assertOk()
            ->assertDontSee(route('reports.create'), false);
    }

    public function test_non_rt_user_receives_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('rt.dashboard'))
            ->assertForbidden();
    }

    public function test_rt_sees_only_reports_from_its_own_rt(): void
    {
        [$rw, $ownRt, $user] = $this->createRtUserWithRw();
        $otherRt = $this->createRt($rw, '002');
        $ownReport = $this->createReport($ownRt, ['title' => 'Laporan Wilayah Sendiri']);
        $otherReport = $this->createReport($otherRt, ['title' => 'Laporan Wilayah Lain']);

        $this->actingAs($user)
            ->get(route('rt.dashboard'))
            ->assertOk()
            ->assertSee($ownReport->title)
            ->assertDontSee($otherReport->title);
    }

    public function test_rt_cannot_open_another_rt_report(): void
    {
        [$rw, $ownRt, $user] = $this->createRtUserWithRw();
        $otherRt = $this->createRt($rw, '002');
        $report = $this->createReport($otherRt);

        $this->actingAs($user)
            ->get(route('rt.reports.show', $report))
            ->assertForbidden();
    }

    public function test_dashboard_totals_are_correct(): void
    {
        [$rt, $user] = $this->createRtUser();
        $new = $this->createReport($rt);
        $processing = $this->transition($this->createReport($rt), ReportStatus::PROCESSING);
        $this->transition($processing, ReportStatus::COMPLETED);
        $this->transition($this->createReport($rt), ReportStatus::REJECTED);

        $this->actingAs($user)
            ->get(route('rt.dashboard'))
            ->assertOk()
            ->assertViewHas('total', 3)
            ->assertViewHas('totalsByStatus', function ($totals) use ($new): bool {
                return $new->status === ReportStatus::NEW
                    && $totals[ReportStatus::NEW->value] === 1
                    && $totals[ReportStatus::PROCESSING->value] === 0
                    && $totals[ReportStatus::COMPLETED->value] === 1
                    && $totals[ReportStatus::REJECTED->value] === 1;
            });
    }

    public function test_status_filter_works(): void
    {
        [$rt, $user] = $this->createRtUser();
        $newReport = $this->createReport($rt, ['title' => 'Hanya Status Baru']);
        $processingReport = $this->transition(
            $this->createReport($rt, ['title' => 'Hanya Status Proses']),
            ReportStatus::PROCESSING,
        );

        $this->actingAs($user)
            ->get(route('rt.dashboard', ['status' => ReportStatus::PROCESSING->value]))
            ->assertOk()
            ->assertSee($processingReport->title)
            ->assertDontSee($newReport->title);
    }

    public function test_search_works_for_ticket_citizen_and_title(): void
    {
        [$rt, $user] = $this->createRtUser();
        $ticketReport = $this->createReport($rt, ['title' => 'Judul Pertama']);
        $citizenReport = $this->createReport($rt, ['title' => 'Judul Kedua']);
        $citizenReport->citizen->update(['name' => 'Nama Warga Unik']);
        $titleReport = $this->createReport($rt, ['title' => 'Jembatan Unik Rusak']);

        $this->actingAs($user)
            ->get(route('rt.dashboard', ['search' => $ticketReport->ticket_number]))
            ->assertSee($ticketReport->title)
            ->assertDontSee($citizenReport->title);

        $this->actingAs($user)
            ->get(route('rt.dashboard', ['search' => 'Nama Warga Unik']))
            ->assertSee($citizenReport->title)
            ->assertDontSee($ticketReport->title);

        $this->actingAs($user)
            ->get(route('rt.dashboard', ['search' => 'Jembatan Unik']))
            ->assertSee($titleReport->title)
            ->assertDontSee($ticketReport->title);
    }

    public function test_pagination_works(): void
    {
        [$rt, $user] = $this->createRtUser();

        foreach (range(1, 11) as $number) {
            $this->createReport($rt, ['title' => "Laporan Nomor {$number}"]);
        }

        $this->actingAs($user)
            ->get(route('rt.dashboard'))
            ->assertOk()
            ->assertViewHas('reports', fn ($reports): bool => $reports->count() === 10
                && $reports->total() === 11
                && $reports->lastPage() === 2);
    }

    public function test_rt_can_change_new_to_processing(): void
    {
        [$rt, $user] = $this->createRtUser();
        $report = $this->createReport($rt);

        $this->actingAs($user)
            ->patch(route('rt.reports.status.update', $report), [
                'status' => ReportStatus::PROCESSING->value,
            ])->assertRedirect(route('rt.reports.show', $report));

        $this->assertSame(ReportStatus::PROCESSING, $report->fresh()->status);
    }

    public function test_rt_can_reject_new(): void
    {
        [$rt, $user] = $this->createRtUser();
        $report = $this->createReport($rt);

        $this->actingAs($user)->patch(route('rt.reports.status.update', $report), [
            'status' => ReportStatus::REJECTED->value,
        ]);

        $this->assertSame(ReportStatus::REJECTED, $report->fresh()->status);
    }

    public function test_rt_can_complete_processing(): void
    {
        [$rt, $user] = $this->createRtUser();
        $report = $this->transition($this->createReport($rt), ReportStatus::PROCESSING);

        $this->actingAs($user)->patch(route('rt.reports.status.update', $report), [
            'status' => ReportStatus::COMPLETED->value,
        ]);

        $this->assertSame(ReportStatus::COMPLETED, $report->fresh()->status);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        [$rt, $user] = $this->createRtUser();
        $report = $this->createReport($rt);

        $this->actingAs($user)
            ->from(route('rt.reports.show', $report))
            ->patch(route('rt.reports.status.update', $report), [
                'status' => ReportStatus::COMPLETED->value,
            ])->assertRedirect(route('rt.reports.show', $report))
            ->assertSessionHasErrors('status');

        $this->assertSame(ReportStatus::NEW, $report->fresh()->status);
    }

    public function test_history_stores_rt_actor_and_note(): void
    {
        [$rt, $user] = $this->createRtUser();
        $report = $this->createReport($rt);

        $this->actingAs($user)->patch(route('rt.reports.status.update', $report), [
            'status' => ReportStatus::PROCESSING->value,
            'note' => 'Sedang ditindaklanjuti oleh Ketua RT.',
        ]);

        $this->assertDatabaseHas('report_histories', [
            'report_id' => $report->id,
            'user_id' => $user->id,
            'old_status' => ReportStatus::NEW->value,
            'new_status' => ReportStatus::PROCESSING->value,
            'note' => 'Sedang ditindaklanjuti oleh Ketua RT.',
        ]);
    }

    /**
     * @return array{Rt, User}
     */
    private function createRtUser(): array
    {
        [, $rt, $user] = $this->createRtUserWithRw();

        return [$rt, $user];
    }

    /**
     * @return array{Rw, Rt, User}
     */
    private function createRtUserWithRw(): array
    {
        $rw = $this->createRw();
        $rt = $this->createRt($rw);
        $user = User::factory()->create([
            'role' => UserRole::RT,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
        ]);

        return [$rw, $rt, $user];
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
