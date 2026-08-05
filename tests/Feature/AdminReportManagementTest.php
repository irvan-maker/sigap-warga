<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_administrator_can_search_reports_by_ticket_title_or_citizen(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $namedCitizen = Citizen::factory()->for($rt)->create(['name' => 'Warga Pencarian']);
        $otherCitizen = Citizen::factory()->for($rt)->create();
        $ticketReport = $this->createReport($otherCitizen, $rt, [
            'ticket_number' => 'SGW-2026-90001',
            'title' => 'Laporan tiket khusus',
        ]);
        $titleReport = $this->createReport($otherCitizen, $rt, ['title' => 'Drainase Melati tersumbat']);
        $citizenReport = $this->createReport($namedCitizen, $rt, ['title' => 'Laporan warga']);
        $unmatchedReport = $this->createReport($otherCitizen, $rt, ['title' => 'Lampu jalan padam']);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.index', ['search' => '90001']))
            ->assertOk()
            ->assertSee($ticketReport->title)
            ->assertDontSee($unmatchedReport->title);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.index', ['search' => 'Melati']))
            ->assertOk()
            ->assertSee($titleReport->title)
            ->assertDontSee($unmatchedReport->title);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.index', ['search' => 'Warga Pencarian']))
            ->assertOk()
            ->assertSee($citizenReport->title)
            ->assertDontSee($unmatchedReport->title);
    }

    public function test_administrator_can_filter_by_status_region_and_reported_at_range(): void
    {
        $firstRw = $this->createRw('001');
        $secondRw = $this->createRw('002');
        $targetRt = $this->createRt($firstRw, '001');
        $otherRt = $this->createRt($secondRw, '001');
        $targetCitizen = Citizen::factory()->for($targetRt)->create();
        $otherCitizen = Citizen::factory()->for($otherRt)->create();

        $target = $this->createReport($targetCitizen, $targetRt, [
            'title' => 'Target filter lengkap',
            'reported_at' => '2026-07-15 10:00:00',
        ]);
        $target->update(['status' => ReportStatus::COMPLETED]);

        $outsideDate = $this->createReport($targetCitizen, $targetRt, [
            'title' => 'Di luar rentang tanggal',
            'reported_at' => '2026-06-30 23:59:59',
        ]);
        $outsideDate->update(['status' => ReportStatus::COMPLETED]);

        $wrongStatus = $this->createReport($targetCitizen, $targetRt, [
            'title' => 'Status berbeda',
            'reported_at' => '2026-07-16 10:00:00',
        ]);
        $otherRegion = $this->createReport($otherCitizen, $otherRt, [
            'title' => 'Wilayah berbeda',
            'reported_at' => '2026-07-17 10:00:00',
        ]);
        $otherRegion->update(['status' => ReportStatus::COMPLETED]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.index', [
                'status' => ReportStatus::COMPLETED->value,
                'rw_id' => $firstRw->id,
                'rt_id' => $targetRt->id,
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertSee($target->title)
            ->assertDontSee($outsideDate->title)
            ->assertDontSee($wrongStatus->title)
            ->assertDontSee($otherRegion->title);
    }

    public function test_report_list_is_paginated(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $citizen = Citizen::factory()->for($rt)->create();

        foreach (range(1, 16) as $number) {
            $this->createReport($citizen, $rt, [
                'title' => "Laporan pagination {$number}",
                'reported_at' => now()->subMinutes($number),
            ]);
        }

        $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertViewHas('reports', fn ($reports): bool => $reports->count() === 15
                && $reports->total() === 16
                && $reports->lastPage() === 2
            );
    }

    public function test_only_active_administrator_can_open_report_management(): void
    {
        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));

        $rtRw = $this->createRw('RT-RW');
        $rt = $this->createRt($rtRw, '001');
        $rw = $this->createRw('RW-RW');
        $unauthorizedUsers = [
            User::factory()->create([
                'role' => UserRole::RT,
                'rw_id' => $rtRw->id,
                'rt_id' => $rt->id,
            ]),
            User::factory()->create([
                'role' => UserRole::RW,
                'rw_id' => $rw->id,
                'rt_id' => null,
            ]),
            User::factory()->create(['role' => UserRole::KELURAHAN]),
        ];

        foreach ($unauthorizedUsers as $user) {
            $this->actingAs($user)
                ->get(route('admin.reports.index'))
                ->assertForbidden();
        }

        $this->actingAs(User::factory()->inactive()->create())
            ->get(route('admin.reports.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.index'))
            ->assertOk();
    }

    public function test_report_list_uses_eager_loading_for_displayed_relations(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $this->createReport(Citizen::factory()->for($rt)->create(), $rt);

        Model::preventLazyLoading();

        try {
            $this->actingAs(User::factory()->create())
                ->get(route('admin.reports.index'))
                ->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_detail_button_uses_the_correct_report_detail_url(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $report = $this->createReport(Citizen::factory()->for($rt)->create(), $rt);
        $detailUrl = route('reports.show', $report);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('href="'.$detailUrl.'"', false)
            ->assertSee('>Detail</a>', false);
    }

    public function test_index_renders_a_detail_link_for_every_report(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $citizen = Citizen::factory()->for($rt)->create();
        $reports = [
            $this->createReport($citizen, $rt, ['title' => 'Laporan pertama']),
            $this->createReport($citizen, $rt, ['title' => 'Laporan kedua']),
        ];

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.index'))
            ->assertOk();

        foreach ($reports as $report) {
            $detailUrl = route('reports.show', $report);

            $response
                ->assertSee('data-report-url="'.$detailUrl.'"', false)
                ->assertSee('href="'.$detailUrl.'"', false);
        }
    }

    public function test_active_administrator_can_open_report_detail_from_the_index(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $report = $this->createReport(Citizen::factory()->for($rt)->create(), $rt);

        $this->actingAs(User::factory()->create())
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee($report->ticket_number);
    }

    private function createRw(string $code): Rw
    {
        return Rw::query()->create([
            'code' => $code,
            'name' => "RW {$code}",
        ]);
    }

    private function createRt(Rw $rw, string $code): Rt
    {
        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => $code,
            'name' => "RT {$code}",
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function createReport(Citizen $citizen, Rt $rt, array $attributes = []): Report
    {
        return Report::factory()->create([
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
            ...$attributes,
        ]);
    }
}
