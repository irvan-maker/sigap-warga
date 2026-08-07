<?php

namespace Tests\Feature;

use App\Enums\LetterStatus;
use App\Enums\LetterType;
use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\VillageLetter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VillageAnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_village_statistics_are_correct_and_dashboard_renders(): void
    {
        [$rw, $rt, $citizen, $user] = $this->region('001', '001', UserRole::ADMIN);
        FamilyCard::query()->create(['rt_id' => $rt->id, 'family_number' => '3671000000000001', 'address' => 'Jalan Desa']);
        Report::factory()->create(['rt_id' => $rt->id, 'citizen_id' => $citizen->id]);
        $this->letter($rt, $citizen, $user);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Statistik Desa')->assertSee('Laporan per Bulan')->assertSee('Surat per RW')
            ->assertViewHas('analytics', fn (array $data): bool => $data['kpis'] === [
                'citizens' => 1, 'family_cards' => 1, 'reports' => 1, 'letters' => 1,
            ]);
    }

    public function test_village_dashboard_keeps_all_six_charts_at_the_bottom_with_secondary_charts_collapsed(): void
    {
        [, , , $user] = $this->region('001', '001', UserRole::KELURAHAN);
        $user->update(['position' => VillagePosition::VILLAGE_SECRETARY]);

        $response = $this->actingAs($user)->get(route('kelurahan.dashboard'))->assertOk();
        $response->assertSeeInOrder(['Daftar Laporan', 'Ranking RT', 'Statistik Desa'])
            ->assertSee('id="completeVillageAnalytics"', false)
            ->assertSee('Lihat Analitik Lengkap');

        foreach (['monthlyReportsChart', 'monthlyLettersChart', 'reportStatusChartAnalytics', 'reportsByRwChart', 'letterStatusChart', 'lettersByRwChart'] as $chartId) {
            $response->assertSee('id="'.$chartId.'"', false);
        }
    }

    public function test_rw_statistics_are_scoped_to_its_own_region(): void
    {
        [$ownRw, $ownRt, $ownCitizen, $rwUser] = $this->region('001', '001', UserRole::RW);
        [, $otherRt, $otherCitizen, $otherUser] = $this->region('002', '001', UserRole::RT);
        Report::factory()->create(['rt_id' => $ownRt->id, 'citizen_id' => $ownCitizen->id]);
        Report::factory()->count(3)->create(['rt_id' => $otherRt->id, 'citizen_id' => $otherCitizen->id]);
        $this->letter($ownRt, $ownCitizen, $rwUser);
        $this->letter($otherRt, $otherCitizen, $otherUser);

        $this->actingAs($rwUser)->get(route('rw.dashboard'))->assertOk()->assertSee('Statistik RW')
            ->assertViewHas('analytics', fn (array $data): bool => $data['kpis']['reports'] === 1
                && $data['kpis']['letters'] === 1 && $data['kpis']['citizens'] === 1 && $data['kpis']['rts'] === 1);
    }

    public function test_rt_demographics_and_data_quality_are_scoped_correctly(): void
    {
        [, $rt, $citizen, $rtUser] = $this->region('001', '001', UserRole::RT);
        $citizen->update(['gender' => 'L', 'birth_date' => now()->subYears(20), 'nik' => null, 'family_card_id' => null]);
        Citizen::factory()->for($rt)->create(['gender' => 'P', 'birth_date' => now()->subYears(4)]);
        [, $otherRt] = $this->region('002', '001', UserRole::RT);
        Citizen::factory()->for($otherRt)->create(['gender' => 'L']);
        FamilyCard::query()->create(['rt_id' => $rt->id, 'family_number' => '3671000000000002', 'address' => 'Jalan RT']);

        $this->actingAs($rtUser)->get(route('rt.dashboard'))->assertOk()->assertSee('Statistik RT')
            ->assertViewHas('analytics', fn (array $data): bool => $data['gender'] === ['male' => 1, 'female' => 1]
                && $data['ages']['data'][0] === 1 && $data['ages']['data'][2] === 1
                && $data['data_quality']['without_nik'] === 2
                && $data['data_quality']['family_cards_without_head'] === 1);
    }

    public function test_rw_rankings_use_aggregate_counts(): void
    {
        [$rw, $firstRt, $citizen, $rwUser] = $this->region('001', '001', UserRole::RW);
        $secondRt = Rt::query()->create(['rw_id' => $rw->id, 'code' => '002', 'name' => 'RT 002']);
        $secondCitizen = Citizen::factory()->for($secondRt)->create();
        Report::factory()->count(2)->create(['rt_id' => $firstRt->id, 'citizen_id' => $citizen->id]);
        Report::factory()->create(['rt_id' => $secondRt->id, 'citizen_id' => $secondCitizen->id]);

        $this->actingAs($rwUser)->get(route('rw.dashboard'))->assertViewHas('analytics', function (array $data) use ($firstRt): bool {
            return $data['rankings']->sortByDesc('reports')->first()['id'] === $firstRt->id;
        });
    }

    public function test_dashboard_query_count_remains_bounded_as_rows_increase(): void
    {
        [, $rt, $citizen, $rwUser] = $this->region('001', '001', UserRole::RW);
        Report::factory()->count(20)->create(['rt_id' => $rt->id, 'citizen_id' => $citizen->id]);
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->actingAs($rwUser)->get(route('rw.dashboard'))->assertOk();

        $this->assertLessThanOrEqual(32, $queries, "Dashboard RW menjalankan {$queries} query; kemungkinan terjadi N+1.");
    }

    /** @return array{Rw, Rt, Citizen, User} */
    private function region(string $rwCode, string $rtCode, UserRole $role): array
    {
        $rw = Rw::query()->create(['code' => $rwCode, 'name' => "RW {$rwCode}"]);
        $rt = Rt::query()->create(['rw_id' => $rw->id, 'code' => $rtCode, 'name' => "RT {$rtCode}"]);
        $citizen = Citizen::factory()->for($rt)->create();
        $user = User::factory()->create([
            'role' => $role,
            'rw_id' => $role === UserRole::RW ? $rw->id : ($role === UserRole::RT ? $rw->id : null),
            'rt_id' => $role === UserRole::RT ? $rt->id : null,
        ]);

        return [$rw, $rt, $citizen, $user];
    }

    private function letter(Rt $rt, Citizen $citizen, User $user): VillageLetter
    {
        return VillageLetter::query()->create([
            'letter_type' => LetterType::DOMICILE_CERTIFICATE,
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
            'submitted_by' => $user->id,
            'purpose' => 'Keperluan pengujian analitik',
            'status' => LetterStatus::SUBMITTED,
        ]);
    }
}
