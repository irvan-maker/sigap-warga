<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelurahanRwManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_rw_index_follows_village_office_read_permission_matrix(): void
    {
        $this->get(route('kelurahan.rws.index'))->assertRedirect(route('login'));

        foreach ([
            $this->villageUser(VillagePosition::SYSTEM_ADMIN),
            $this->villageUser(VillagePosition::VILLAGE_SECRETARY),
            $this->villageUser(VillagePosition::VILLAGE_HEAD),
        ] as $user) {
            $this->actingAs($user)->get(route('kelurahan.rws.index'))->assertOk();
        }

        $rw = $this->rw('001');
        $rt = $this->rt($rw, '001');
        $denied = [
            User::factory()->create([
                'role' => UserRole::RW,
                'position' => null,
                'rw_id' => $rw->id,
                'rt_id' => null,
            ]),
            User::factory()->create([
                'role' => UserRole::RT,
                'position' => null,
                'rw_id' => $rw->id,
                'rt_id' => $rt->id,
            ]),
            $this->villageUser(VillagePosition::SYSTEM_ADMIN, false),
        ];

        foreach ($denied as $user) {
            $this->actingAs($user)->get(route('kelurahan.rws.index'))->assertForbidden();
        }
    }

    public function test_rw_mutations_follow_village_office_write_permission_matrix(): void
    {
        foreach ([
            $this->villageUser(VillagePosition::SYSTEM_ADMIN),
            $this->villageUser(VillagePosition::VILLAGE_SECRETARY),
        ] as $index => $user) {
            $this->actingAs($user)
                ->post(route('kelurahan.rws.store'), [
                    'code' => "RW-WRITE-{$index}",
                    'name' => "RW Write {$index}",
                ])
                ->assertRedirect();
        }

        $head = $this->villageUser(VillagePosition::VILLAGE_HEAD);
        $this->actingAs($head)
            ->post(route('kelurahan.rws.store'), ['code' => 'RW-HEAD', 'name' => 'RW Head'])
            ->assertForbidden();
    }

    public function test_search_filter_and_pagination_work_without_lazy_loading(): void
    {
        Rw::query()->create(['code' => 'TARGET', 'name' => 'Anggrek', 'is_active' => false]);
        foreach (range(1, 15) as $number) {
            $this->rw(sprintf('%03d', $number));
        }

        Model::preventLazyLoading();
        try {
            $this->actingAs($this->secretary())
                ->get(route('kelurahan.rws.index', ['search' => 'Anggrek', 'status' => 'inactive']))
                ->assertOk()
                ->assertSee('TARGET')
                ->assertViewHas('rws', fn ($rws): bool => $rws->total() === 1);
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->actingAs($this->secretary())
            ->get(route('kelurahan.rws.index'))
            ->assertViewHas('rws', fn ($rws): bool => $rws->count() === 15 && $rws->lastPage() === 2);
    }

    public function test_secretary_can_create_edit_and_toggle_rw_but_duplicate_code_is_rejected(): void
    {
        $user = $this->secretary();
        $this->actingAs($user)->post(route('kelurahan.rws.store'), ['code' => 'RW-01', 'name' => 'RW Satu'])->assertRedirect();
        $rw = Rw::query()->where('code', 'RW-01')->sole();
        $this->actingAs($user)->put(route('kelurahan.rws.update', $rw), ['code' => 'RW-01', 'name' => 'RW Satu Baru'])->assertRedirect(route('kelurahan.rws.edit', $rw));
        $this->assertSame('RW Satu Baru', $rw->fresh()->name);
        $this->actingAs($user)->post(route('kelurahan.rws.store'), ['code' => 'RW-01', 'name' => 'Duplikat'])->assertSessionHasErrors('code');
        $this->actingAs($user)->patch(route('kelurahan.rws.status.toggle', $rw))->assertRedirect();
        $this->assertFalse($rw->fresh()->is_active);
    }

    public function test_rw_with_active_dependency_cannot_be_deactivated_and_dashboard_has_link(): void
    {
        $rw = $this->rw('001');
        $this->rt($rw, '001');
        $user = $this->secretary();
        $this->actingAs($user)->patch(route('kelurahan.rws.status.toggle', $rw))->assertSessionHasErrors('status');
        $this->assertTrue($rw->fresh()->is_active);
        $this->actingAs($user)->get(route('kelurahan.dashboard'))->assertSee('href="'.route('kelurahan.rws.index').'"', false);
    }

    private function secretary(): User
    {
        return $this->villageUser(VillagePosition::VILLAGE_SECRETARY);
    }

    private function villageUser(VillagePosition $position, bool $active = true): User
    {
        return User::factory()->create([
            'role' => UserRole::KELURAHAN,
            'position' => $position,
            'is_active' => $active,
            'rw_id' => null,
            'rt_id' => null,
        ]);
    }

    private function rw(string $code): Rw
    {
        return Rw::query()->create(['code' => $code, 'name' => "RW {$code}"]);
    }

    private function rt(Rw $rw, string $code): Rt
    {
        return Rt::query()->create(['rw_id' => $rw->id, 'code' => $code, 'name' => "RT {$code}"]);
    }
}
