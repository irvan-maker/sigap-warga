<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_rt_cannot_open_report_from_another_rt_through_generic_endpoint(): void
    {
        $rw = $this->createRw('001');
        $ownRt = $this->createRt($rw, '001');
        $otherRt = $this->createRt($rw, '002');
        $user = User::factory()->create([
            'role' => UserRole::RT,
            'rw_id' => $rw->id,
            'rt_id' => $ownRt->id,
        ]);

        $this->actingAs($user)
            ->get(route('reports.show', $this->createReport($otherRt)))
            ->assertForbidden();
    }

    public function test_rt_can_open_report_from_its_own_rt_through_generic_endpoint(): void
    {
        $rw = $this->createRw('001');
        $rt = $this->createRt($rw, '001');
        $user = User::factory()->create([
            'role' => UserRole::RT,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
        ]);

        $this->actingAs($user)
            ->get(route('reports.show', $this->createReport($rt)))
            ->assertOk();
    }

    public function test_rw_cannot_open_report_from_another_rw_through_generic_endpoint(): void
    {
        $ownRw = $this->createRw('001');
        $otherRw = $this->createRw('002');
        $user = User::factory()->create([
            'role' => UserRole::RW,
            'rw_id' => $ownRw->id,
            'rt_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('reports.show', $this->createReport($this->createRt($otherRw, '001'))))
            ->assertForbidden();
    }

    public function test_rw_can_open_report_from_its_own_rw_through_generic_endpoint(): void
    {
        $rw = $this->createRw('001');
        $user = User::factory()->create([
            'role' => UserRole::RW,
            'rw_id' => $rw->id,
            'rt_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('reports.show', $this->createReport($this->createRt($rw, '001'))))
            ->assertOk();
    }

    public function test_kelurahan_can_open_report_through_generic_endpoint(): void
    {
        $report = $this->createReport($this->createRt($this->createRw('001'), '001'));
        $user = User::factory()->create(['role' => UserRole::KELURAHAN]);

        $this->actingAs($user)
            ->get(route('reports.show', $report))
            ->assertOk();
    }

    public function test_inactive_kelurahan_cannot_open_report_through_generic_endpoint(): void
    {
        $report = $this->createReport($this->createRt($this->createRw('001'), '001'));
        $user = User::factory()->inactive()->create(['role' => UserRole::KELURAHAN]);

        $this->actingAs($user)
            ->get(route('reports.show', $report))
            ->assertForbidden();
    }

    public function test_administrator_can_still_open_report_through_generic_endpoint(): void
    {
        $report = $this->createReport($this->createRt($this->createRw('001'), '001'));

        $this->actingAs(User::factory()->create())
            ->get(route('reports.show', $report))
            ->assertOk();
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

    private function createReport(Rt $rt): Report
    {
        $citizen = Citizen::factory()->for($rt)->create();

        return Report::factory()->create([
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
        ]);
    }
}
