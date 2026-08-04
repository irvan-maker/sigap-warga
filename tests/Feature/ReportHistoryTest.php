<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Services\ReportStatusService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_new_history_is_created(): void
    {
        $report = $this->createReport();
        $history = $report->histories()->sole();

        $this->assertNull($history->old_status);
        $this->assertSame(ReportStatus::NEW, $history->new_status);
        $this->assertNull($history->user_id);
    }

    public function test_new_can_transition_to_processing(): void
    {
        $report = $this->transition($this->createReport(), ReportStatus::PROCESSING);

        $this->assertSame(ReportStatus::PROCESSING, $report->status);
        $this->assertHistoryTransition($report, ReportStatus::NEW, ReportStatus::PROCESSING);
    }

    public function test_new_can_transition_to_rejected(): void
    {
        $report = $this->transition($this->createReport(), ReportStatus::REJECTED);

        $this->assertSame(ReportStatus::REJECTED, $report->status);
        $this->assertHistoryTransition($report, ReportStatus::NEW, ReportStatus::REJECTED);
    }

    public function test_processing_can_transition_to_completed(): void
    {
        $report = $this->transition($this->createReport(), ReportStatus::PROCESSING);
        $report = $this->transition($report, ReportStatus::COMPLETED);

        $this->assertSame(ReportStatus::COMPLETED, $report->status);
        $this->assertHistoryTransition($report, ReportStatus::PROCESSING, ReportStatus::COMPLETED);
    }

    public function test_processing_can_transition_to_rejected(): void
    {
        $report = $this->transition($this->createReport(), ReportStatus::PROCESSING);
        $report = $this->transition($report, ReportStatus::REJECTED);

        $this->assertSame(ReportStatus::REJECTED, $report->status);
        $this->assertHistoryTransition($report, ReportStatus::PROCESSING, ReportStatus::REJECTED);
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $report = $this->createReport();

        $this->expectException(DomainException::class);

        $this->transition($report, ReportStatus::COMPLETED);
    }

    public function test_terminal_status_cannot_transition(): void
    {
        $report = $this->transition($this->createReport(), ReportStatus::REJECTED);

        $this->expectException(DomainException::class);

        $this->transition($report, ReportStatus::PROCESSING);
    }

    public function test_completed_status_cannot_transition(): void
    {
        $report = $this->transition($this->createReport(), ReportStatus::PROCESSING);
        $report = $this->transition($report, ReportStatus::COMPLETED);

        $this->expectException(DomainException::class);

        $this->transition($report, ReportStatus::REJECTED);
    }

    public function test_same_status_transition_is_rejected(): void
    {
        $report = $this->createReport();

        $this->expectException(DomainException::class);

        $this->transition($report, ReportStatus::NEW);
    }

    public function test_history_stores_actor_and_note(): void
    {
        $actor = User::factory()->create();
        $report = app(ReportStatusService::class)->transition(
            $this->createReport(),
            ReportStatus::PROCESSING,
            $actor,
            'Laporan sedang ditindaklanjuti.',
        );
        $history = $report->histories()->latest('id')->firstOrFail();

        $this->assertTrue($history->user->is($actor));
        $this->assertSame('Laporan sedang ditindaklanjuti.', $history->note);
    }

    public function test_report_deletion_removes_its_histories(): void
    {
        $report = $this->createReport();
        $reportId = $report->id;

        $report->delete();

        $this->assertDatabaseMissing('report_histories', ['report_id' => $reportId]);
    }

    public function test_user_deletion_keeps_history_and_sets_user_id_to_null(): void
    {
        $actor = User::factory()->create();
        $report = app(ReportStatusService::class)->transition(
            $this->createReport(),
            ReportStatus::PROCESSING,
            $actor,
        );
        $history = $report->histories()->latest('id')->firstOrFail();

        $actor->delete();

        $this->assertDatabaseHas('report_histories', [
            'id' => $history->id,
            'user_id' => null,
        ]);
    }

    private function transition(Report $report, ReportStatus $status): Report
    {
        return app(ReportStatusService::class)->transition($report, $status);
    }

    private function assertHistoryTransition(
        Report $report,
        ReportStatus $oldStatus,
        ReportStatus $newStatus,
    ): void {
        $this->assertDatabaseHas('report_histories', [
            'report_id' => $report->id,
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
        ]);
    }

    private function createReport(): Report
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
        $rt = Rt::query()->create(['rw_id' => $rw->id, 'code' => '001', 'name' => 'RT 001']);
        $citizen = Citizen::factory()->for($rt)->create();

        return Report::factory()->create([
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
        ]);
    }
}
