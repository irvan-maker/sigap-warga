<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RwRtManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_only_active_rw_can_open_rt_management(): void
    {
        $this->get(route('rw.rts.index'))->assertRedirect(route('login'));
        $rw = $this->rw('001');
        $rt = $this->rt($rw, '001');
        $denied = [User::factory()->create(), User::factory()->create(['role' => UserRole::KELURAHAN]), User::factory()->create(['role' => UserRole::RT, 'rw_id' => $rw->id, 'rt_id' => $rt->id]), User::factory()->inactive()->create(['role' => UserRole::RW, 'rw_id' => $rw->id, 'rt_id' => null])];
        foreach ($denied as $user) {
            $this->actingAs($user)->get(route('rw.rts.index'))->assertForbidden();
        }
        $this->actingAs($this->rwUser($rw))->get(route('rw.rts.index'))->assertOk();
    }

    public function test_rw_only_sees_and_can_manage_its_own_rts(): void
    {
        $one = $this->rw('001');
        $two = $this->rw('002');
        $own = $this->rt($one, '001');
        $own->update(['name' => 'RT Milik Sendiri']);
        $other = $this->rt($two, '001');
        $other->update(['name' => 'RT Wilayah Lain']);
        $user = $this->rwUser($one);
        $this->actingAs($user)->get(route('rw.rts.index'))->assertSee($own->name)->assertDontSee($other->name);
        $this->actingAs($user)->get(route('rw.rts.edit', $other))->assertForbidden();
        $this->actingAs($user)->put(route('rw.rts.update', $other), ['code' => 'X', 'name' => 'X'])->assertForbidden();
        $this->actingAs($user)->patch(route('rw.rts.status.toggle', $other))->assertForbidden();
    }

    public function test_create_ignores_manipulated_rw_id_and_code_uniqueness_is_scoped_to_rw(): void
    {
        $one = $this->rw('001');
        $two = $this->rw('002');
        $this->rt($two, '001');
        $user = $this->rwUser($one);
        $this->actingAs($user)->post(route('rw.rts.store'), ['code' => '001', 'name' => 'RT Baru', 'rw_id' => $two->id])->assertRedirect();
        $created = Rt::query()->where('rw_id', $one->id)->sole();
        $this->assertSame($one->id, $created->rw_id);
        $this->actingAs($user)->post(route('rw.rts.store'), ['code' => '001', 'name' => 'Duplikat'])->assertSessionHasErrors('code');
    }

    public function test_search_filter_pagination_edit_toggle_dashboard_and_lazy_loading_work(): void
    {
        $rw = $this->rw('001');
        $target = Rt::query()->create(['rw_id' => $rw->id, 'code' => 'TARGET', 'name' => 'Anggrek', 'is_active' => false]);
        foreach (range(1, 15) as $n) {
            $this->rt($rw, sprintf('%03d', $n));
        }$user = $this->rwUser($rw);
        Model::preventLazyLoading();
        try {
            $this->actingAs($user)->get(route('rw.rts.index', ['search' => 'Anggrek', 'status' => 'inactive']))->assertSee('TARGET')->assertViewHas('rts', fn ($rts) => $rts->total() === 1);
        } finally {
            Model::preventLazyLoading(false);
        }
        $this->actingAs($user)->get(route('rw.rts.index'))->assertViewHas('rts', fn ($rts) => $rts->count() === 15 && $rts->lastPage() === 2);
        $this->actingAs($user)->put(route('rw.rts.update', $target), ['code' => 'TARGET', 'name' => 'Nama Baru', 'whatsapp_number' => '08123', 'rw_id' => 999])->assertRedirect();
        $this->assertSame($rw->id, $target->fresh()->rw_id);
        $this->assertSame('Nama Baru', $target->fresh()->name);
        $this->actingAs($user)->patch(route('rw.rts.status.toggle', $target))->assertRedirect();
        $this->assertTrue($target->fresh()->is_active);
        $this->actingAs($user)->patch(route('rw.rts.status.toggle', $target))->assertRedirect();
        $this->assertFalse($target->fresh()->is_active);
        $this->actingAs($user)->get(route('rw.dashboard'))->assertSee('href="'.route('rw.rts.index').'"', false);
    }

    private function rw(string $code): Rw
    {
        return Rw::query()->create(['code' => $code, 'name' => "RW {$code}"]);
    }

    private function rt(Rw $rw, string $code): Rt
    {
        return Rt::query()->create(['rw_id' => $rw->id, 'code' => $code, 'name' => "RT {$code}"]);
    }

    private function rwUser(Rw $rw): User
    {
        return User::factory()->create(['role' => UserRole::RW, 'rw_id' => $rw->id, 'rt_id' => null]);
    }
}
