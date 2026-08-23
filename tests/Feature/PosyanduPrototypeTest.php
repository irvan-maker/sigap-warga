<?php

namespace Tests\Feature;

use App\Enums\PosyanduLifeCycleGroup;
use App\Enums\PosyanduStaffRole;
use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\PosyanduSite;
use App\Models\PosyanduStaffAssignment;
use App\Models\PosyanduVisit;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PosyanduPrototypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['modules.posyandu.enabled' => true]);
    }

    public function test_only_explicitly_assigned_staff_can_open_individual_posyandu_records(): void
    {
        [$rw, $rt] = $this->region();
        $assigned = $this->rtUser($rw, $rt);
        $unassigned = $this->rtUser($rw, $rt);
        $site = PosyanduSite::query()->create(['rt_id' => $rt->id, 'name' => 'Posyandu Melati']);
        PosyanduStaffAssignment::query()->create([
            'posyandu_site_id' => $site->id,
            'user_id' => $assigned->id,
            'role' => PosyanduStaffRole::CADRE,
        ]);

        $this->actingAs($assigned)->get(route('posyandu.index'))->assertOk()->assertSee('Posyandu Melati');
        $this->actingAs($unassigned)->get(route('posyandu.index'))->assertForbidden();
    }

    public function test_assigned_staff_can_record_same_rt_visit_and_sensitive_notes_are_encrypted(): void
    {
        [$rw, $rt] = $this->region();
        $staff = $this->rtUser($rw, $rt);
        $citizen = Citizen::factory()->for($rt)->create();
        $site = PosyanduSite::query()->create(['rt_id' => $rt->id, 'name' => 'Posyandu Melati']);
        PosyanduStaffAssignment::query()->create([
            'posyandu_site_id' => $site->id,
            'user_id' => $staff->id,
            'role' => PosyanduStaffRole::CADRE,
        ]);

        $this->actingAs($staff)->post(route('posyandu.visits.store'), [
            'posyandu_site_id' => $site->id,
            'citizen_id' => $citizen->id,
            'visited_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'life_cycle_group' => PosyanduLifeCycleGroup::INFANT_TODDLER->value,
            'weight_kg' => 12.5,
            'height_cm' => 88,
            'notes' => 'Catatan kesehatan yang sensitif',
            'follow_up' => 'Kontrol kembali bulan depan',
            'referral_required' => true,
        ])->assertRedirect(route('posyandu.index'))->assertSessionHasNoErrors();

        $visit = PosyanduVisit::query()->sole();
        $raw = DB::table('posyandu_visits')->where('id', $visit->id)->first();
        $this->assertSame('Catatan kesehatan yang sensitif', $visit->notes);
        $this->assertNotSame('Catatan kesehatan yang sensitif', $raw->notes);
        $this->assertNotSame('Kontrol kembali bulan depan', $raw->follow_up);
        $this->assertDatabaseHas('posyandu_audit_events', [
            'user_id' => $staff->id,
            'action' => 'VISIT_RECORDED',
            'subject_id' => $visit->id,
        ]);
    }

    public function test_staff_cannot_record_a_citizen_from_another_rt(): void
    {
        [$rw, $rt, $otherRt] = $this->region(withSecondRt: true);
        $staff = $this->rtUser($rw, $rt);
        $otherCitizen = Citizen::factory()->for($otherRt)->create();
        $site = PosyanduSite::query()->create(['rt_id' => $rt->id, 'name' => 'Posyandu Melati']);
        PosyanduStaffAssignment::query()->create([
            'posyandu_site_id' => $site->id,
            'user_id' => $staff->id,
            'role' => PosyanduStaffRole::CADRE,
        ]);

        $this->actingAs($staff)->post(route('posyandu.visits.store'), [
            'posyandu_site_id' => $site->id,
            'citizen_id' => $otherCitizen->id,
            'visited_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'life_cycle_group' => PosyanduLifeCycleGroup::PRODUCTIVE_AGE->value,
        ])->assertStatus(422);

        $this->assertDatabaseCount('posyandu_visits', 0);
    }

    public function test_system_admin_configures_staff_but_does_not_see_health_notes(): void
    {
        [$rw, $rt] = $this->region();
        $admin = User::factory()->create();
        $staff = $this->rtUser($rw, $rt);

        $this->actingAs($admin)->post(route('admin.posyandu.sites.store'), [
            'rt_id' => $rt->id,
            'name' => 'Posyandu Melati',
            'address' => 'Balai warga',
        ])->assertRedirect(route('admin.posyandu.index'));
        $site = PosyanduSite::query()->sole();
        $this->actingAs($admin)->post(route('admin.posyandu.staff.store'), [
            'posyandu_site_id' => $site->id,
            'user_id' => $staff->id,
            'role' => PosyanduStaffRole::HEALTH_OFFICER->value,
        ])->assertRedirect(route('admin.posyandu.index'));

        $this->actingAs($admin)
            ->get(route('admin.posyandu.index'))
            ->assertOk()
            ->assertSee('isi pelayanan tidak ditampilkan')
            ->assertDontSee('Catatan kesehatan');
    }

    /** @return array{0: Rw, 1: Rt, 2?: Rt} */
    private function region(bool $withSecondRt = false): array
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);
        $first = Rt::query()->create(['rw_id' => $rw->id, 'code' => '001', 'name' => 'RT 001']);

        if (! $withSecondRt) {
            return [$rw, $first];
        }

        return [
            $rw,
            $first,
            Rt::query()->create(['rw_id' => $rw->id, 'code' => '002', 'name' => 'RT 002']),
        ];
    }

    private function rtUser(Rw $rw, Rt $rt): User
    {
        return User::factory()->create([
            'role' => UserRole::RT,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
        ]);
    }
}
