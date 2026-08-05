<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Services\ReportStatusService;
use Illuminate\Database\Eloquent\Model;
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
        $this->get(route('kelurahan.dashboard'))->assertRedirect(route('login'));
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

    public function test_system_administrator_can_access(): void
    {
        $this->actingAs($this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN))
            ->get(route('kelurahan.dashboard'))
            ->assertOk();
    }

    public function test_kelurahan_can_access(): void
    {
        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Kelurahan');
    }

    public function test_kpi_cards_link_to_the_report_list_with_correct_status_filters(): void
    {
        $response = $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('kelurahan.reports.index').'#laporan"', false);

        foreach (ReportStatus::cases() as $status) {
            $response->assertSee(
                'href="'.route('kelurahan.reports.index', ['status' => $status->value]).'#laporan"',
                false,
            );
        }

        $response->assertDontSee('href="#"', false)
            ->assertDontSee('href=""', false);
    }

    public function test_quick_actions_follow_the_village_office_permission_matrix(): void
    {
        $systemAdminResponse = $this->actingAs($this->createVillageOfficer(VillagePosition::SYSTEM_ADMIN))
            ->get(route('kelurahan.dashboard'));
        $systemAdminResponse->assertSee('Kelola Laporan')
            ->assertSee('Kelola RW')
            ->assertSee('Kelola Akun Petugas');

        $secretaryResponse = $this->actingAs($this->createVillageOfficer(VillagePosition::VILLAGE_SECRETARY))
            ->get(route('kelurahan.dashboard'));
        $secretaryResponse->assertSee('Kelola Laporan')
            ->assertSee('Kelola RW')
            ->assertSee('Kelola Akun Petugas');

        $headResponse = $this->actingAs($this->createVillageOfficer(VillagePosition::VILLAGE_HEAD))
            ->get(route('kelurahan.dashboard'));
        $headResponse->assertSee('Kelola Laporan')
            ->assertSee('Lihat RW')
            ->assertSee('Ringkasan Pekerjaan')
            ->assertDontSee('Kelola Akun Petugas');
    }

    public function test_today_summary_counts_real_report_activity(): void
    {
        $rt = $this->createRt($this->createRw());
        $this->createReport($rt, ['reported_at' => now()]);
        $this->createReport($rt, ['reported_at' => now()->subDay()]);
        $completed = $this->transition($this->createReport($rt), ReportStatus::PROCESSING);
        $this->transition($completed, ReportStatus::COMPLETED);

        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard'))
            ->assertViewHas('todaySummary', fn (array $summary): bool => $summary['created'] === 2
                && $summary['new'] === 2
                && $summary['processing'] === 0
                && $summary['completed'] === 1
                && $summary['active_rws'] === 1
                && $summary['active_rts'] === 1);
    }

    public function test_processing_reports_unchanged_for_more_than_three_days_are_prioritized(): void
    {
        $rt = $this->createRt($this->createRw());
        $stale = $this->transition($this->createReport($rt), ReportStatus::PROCESSING);
        $fresh = $this->transition($this->createReport($rt), ReportStatus::PROCESSING);
        $stale->histories()->update([
            'created_at' => now()->subDays(4),
        ]);

        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard'))
            ->assertViewHas('attentionSummary', fn (array $summary): bool => $summary['stale_processing'] === 1);

        $this->assertNotSame($stale->id, $fresh->id);
    }

    public function test_region_summary_uses_loaded_aggregate_counts_without_lazy_loading(): void
    {
        $rw = $this->createRw();
        $rt = $this->createRt($rw);
        $this->createReport($rt);
        Model::preventLazyLoading();

        try {
            $this->actingAs($this->createKelurahanUser())
                ->get(route('kelurahan.dashboard'))
                ->assertOk()
                ->assertViewHas('regionSummary', fn ($summary): bool => $summary->first()->active_rts_count === 1
                    && $summary->first()->reports_count === 1);
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_report_table_has_operational_title_and_accessible_detail_navigation(): void
    {
        $report = $this->createReport($this->createRt($this->createRw()));
        $detailUrl = route('kelurahan.reports.show', $report);

        $this->actingAs($this->createKelurahanUser())
            ->get(route('kelurahan.dashboard'))
            ->assertOk()
            ->assertSee('Daftar Laporan')
            ->assertDontSee('Laporan Terbaru')
            ->assertSee('data-row-url="'.$detailUrl.'"', false)
            ->assertSee('href="'.$detailUrl.'" target="_blank" rel="noopener"', false)
            ->assertDontSee('onclick=', false);
    }

    public function test_region_rows_link_to_the_destination_allowed_for_each_position(): void
    {
        $rw = $this->createRw();
        $editUrl = route('kelurahan.rws.edit', $rw);

        $this->actingAs($this->createVillageOfficer(VillagePosition::VILLAGE_SECRETARY))
            ->get(route('kelurahan.dashboard'))
            ->assertSee('data-row-url="'.$editUrl.'"', false)
            ->assertSee('href="'.$editUrl.'"', false);

        $this->actingAs($this->createVillageOfficer(VillagePosition::VILLAGE_HEAD))
            ->get(route('kelurahan.dashboard'))
            ->assertSee('data-row-url="'.route('kelurahan.rws.index').'"', false)
            ->assertDontSee($editUrl, false);
    }

    public function test_region_summary_and_header_render_operational_context(): void
    {
        $rt = $this->createRt($this->createRw());
        Citizen::factory()->for($rt)->count(2)->create();
        $this->createReport($rt);
        $officer = $this->createVillageOfficer(VillagePosition::VILLAGE_SECRETARY);

        $this->actingAs($officer)
            ->get(route('kelurahan.dashboard'))
            ->assertOk()
            ->assertSee((string) config('village.name'))
            ->assertSee($officer->name)
            ->assertSee(VillagePosition::VILLAGE_SECRETARY->label())
            ->assertSee('Layanan aktif')
            ->assertSee('3 warga')
            ->assertSee('1 laporan')
            ->assertSee('1 Baru')
            ->assertSee('0 Diproses')
            ->assertSee('0 Selesai')
            ->assertSee('0 Ditolak');
    }

    public function test_inactive_village_officer_cannot_access(): void
    {
        $this->actingAs($this->createVillageOfficer(VillagePosition::VILLAGE_SECRETARY, false))
            ->get(route('kelurahan.dashboard'))
            ->assertForbidden();
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
            ->assertViewHas('totalsByStatus', fn ($totals): bool => $totals[ReportStatus::NEW->value] === 1
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
            ->assertViewHas('totalsByRw', fn ($totals): bool => $totals[$firstRw->id] === 2 && $totals[$secondRw->id] === 1
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
            ->assertViewHas('totalsByRt', fn ($totals): bool => $totals[$firstRt->id] === 1 && $totals[$secondRt->id] === 2
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
        return $this->createVillageOfficer(VillagePosition::VILLAGE_SECRETARY);
    }

    private function createVillageOfficer(VillagePosition $position, bool $isActive = true): User
    {
        return User::factory()->create([
            'role' => $position === VillagePosition::SYSTEM_ADMIN ? UserRole::ADMIN : UserRole::KELURAHAN,
            'position' => $position,
            'is_active' => $isActive,
            'rw_id' => null,
            'rt_id' => null,
        ]);
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
