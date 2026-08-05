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

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_open_dashboard(): void
    {
        $admin = User::factory()->create(['name' => 'Admin SIGAP']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Admin SIGAP')
            ->assertSee('Pusat Kendali')
            ->assertSee('Statistik Utama')
            ->assertSee('Laporan Terbaru');
    }

    public function test_admin_dashboard_renders_report_management_link(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelola Laporan')
            ->assertSee('href="'.route('admin.reports.index').'"', false);
    }

    public function test_rt_user_is_redirected_without_seeing_admin_dashboard_data(): void
    {
        [$rw, $rt] = $this->createRegion();
        $user = User::factory()->create([
            'role' => UserRole::RT,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('rt.dashboard'))
            ->assertDontSee('Pusat Kendali')
            ->assertDontSee('Statistik Utama');
    }

    public function test_dashboard_main_statistics_match_database_data(): void
    {
        $admin = User::factory()->create();
        [$activeRw, $activeRt] = $this->createRegion();
        $inactiveRw = Rw::query()->create([
            'code' => 'RW-INACTIVE',
            'name' => 'RW Tidak Aktif',
            'is_active' => false,
        ]);
        $inactiveRt = Rt::query()->create([
            'rw_id' => $inactiveRw->id,
            'code' => 'RT-INACTIVE',
            'name' => 'RT Tidak Aktif',
            'is_active' => false,
        ]);

        $activeCitizen = Citizen::factory()->for($activeRt)->create();
        Citizen::factory()->for($inactiveRt)->create();

        foreach (ReportStatus::cases() as $status) {
            $report = Report::factory()->create([
                'citizen_id' => $activeCitizen->id,
                'rt_id' => $activeRt->id,
                'title' => "Laporan {$status->value}",
            ]);

            if ($status !== ReportStatus::NEW) {
                $report->update(['status' => $status]);
            }
        }

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('totalCitizens', 2)
            ->assertViewHas('totalActiveRws', 1)
            ->assertViewHas('totalActiveRts', 1)
            ->assertViewHas('totalReports', 4)
            ->assertViewHas('totalsByStatus', function ($totals): bool {
                foreach (ReportStatus::cases() as $status) {
                    if ($totals[$status->value] !== 1) {
                        return false;
                    }
                }

                return true;
            });
    }

    public function test_dashboard_uses_eager_loading_and_handles_nullable_history_actor(): void
    {
        $admin = User::factory()->create();
        [, $rt] = $this->createRegion();

        foreach (range(1, 10) as $number) {
            $citizen = Citizen::factory()->for($rt)->create();
            Report::factory()->create([
                'citizen_id' => $citizen->id,
                'rt_id' => $rt->id,
                'title' => "Laporan Wilayah {$number}",
            ]);
        }

        Model::preventLazyLoading();

        try {
            $this->actingAs($admin)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Laporan Wilayah 10')
                ->assertSee('Sistem');
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_dashboard_chart_data_matches_reports_from_the_last_six_months(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(10));

        $admin = User::factory()->create();
        [, $rt] = $this->createRegion();
        $citizen = Citizen::factory()->for($rt)->create();

        foreach ([5 => 1, 3 => 2, 0 => 3] as $monthsAgo => $total) {
            Report::factory()->count($total)->create([
                'citizen_id' => $citizen->id,
                'rt_id' => $rt->id,
                'reported_at' => now()->startOfMonth()->subMonths($monthsAgo)->addDay(),
            ]);
        }

        Report::factory()->create([
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
            'reported_at' => now()->startOfMonth()->subMonths(6),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('monthlyReportChart', function (array $chart): bool {
                return $chart['data']->all() === [1, 0, 2, 0, 0, 3]
                    && $chart['labels']->count() === 6;
            })
            ->assertViewHas('reportStatusChart', function (array $chart): bool {
                return $chart['labels']->all() === ['NEW', 'PROCESSING', 'COMPLETED', 'REJECTED']
                    && $chart['data']->all() === [7, 0, 0, 0];
            });
    }

    /** @return array{Rw, Rt} */
    private function createRegion(): array
    {
        $rw = Rw::query()->create([
            'code' => 'RW-TEST',
            'name' => 'RW Test',
            'is_active' => true,
        ]);
        $rt = Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => 'RT-TEST',
            'name' => 'RT Test',
            'is_active' => true,
        ]);

        return [$rw, $rt];
    }
}
