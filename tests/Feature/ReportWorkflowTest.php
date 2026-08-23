<?php

namespace Tests\Feature;

use App\Enums\ReportDispositionStatus;
use App\Enums\ReportHandlingLevel;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_report_moves_from_rt_to_rw_and_can_be_acknowledged(): void
    {
        [$rw, $firstRt] = $this->region();
        $rtUser = $this->rtUser($rw, $firstRt);
        $rwUser = $this->rwUser($rw);
        $report = $this->report($firstRt);

        $this->actingAs($rtUser)->post(route('rt.reports.forward', $report), [
            'target_level' => ReportHandlingLevel::RW->value,
            'reason' => 'Perbaikan membutuhkan koordinasi dan anggaran RW.',
        ])->assertRedirect(route('rt.reports.show', $report))->assertSessionHasNoErrors();

        $report->refresh();
        $this->assertSame(ReportStatus::FORWARDED, $report->status);
        $this->assertSame(ReportHandlingLevel::RW, $report->current_handling_level);
        $this->assertSame($rw->id, $report->current_rw_id);
        $this->assertNull($report->current_rt_id);
        $this->assertSame(ReportDispositionStatus::PENDING, $report->dispositions()->sole()->status);

        $this->actingAs($rwUser)
            ->get(route('rw.reports.show', $report))
            ->assertOk()
            ->assertSee('Terima Disposisi');

        $this->actingAs($rwUser)
            ->post(route('rw.reports.acknowledge', $report))
            ->assertRedirect(route('rw.reports.show', $report))
            ->assertSessionHasNoErrors();

        $report->refresh();
        $this->assertSame(ReportStatus::PROCESSING, $report->status);
        $this->assertSame($rwUser->id, $report->assigned_user_id);
        $this->assertNotNull($report->acknowledged_at);
        $this->assertSame(
            ReportDispositionStatus::ACKNOWLEDGED,
            $report->dispositions()->sole()->status,
        );
    }

    public function test_rw_can_return_work_to_an_rt_in_the_same_rw_without_erasing_history(): void
    {
        [$rw, $firstRt, $secondRt] = $this->region(withSecondRt: true);
        $report = $this->report($firstRt);
        $firstRtUser = $this->rtUser($rw, $firstRt);
        $secondRtUser = $this->rtUser($rw, $secondRt);
        $rwUser = $this->rwUser($rw);

        $this->actingAs($firstRtUser)->post(route('rt.reports.forward', $report), [
            'target_level' => ReportHandlingLevel::RW->value,
            'reason' => 'Perlu koordinasi RW.',
        ]);
        $this->actingAs($rwUser)->post(route('rw.reports.acknowledge', $report));
        $this->actingAs($rwUser)->post(route('rw.reports.forward', $report), [
            'target_level' => ReportHandlingLevel::RT->value,
            'target_rt_id' => $secondRt->id,
            'reason' => 'Lokasi penanganan berada pada batas RT kedua.',
        ])->assertSessionHasNoErrors();

        $report->refresh();
        $this->assertSame(ReportHandlingLevel::RT, $report->current_handling_level);
        $this->assertSame($secondRt->id, $report->current_rt_id);
        $this->assertSame($firstRt->id, $report->rt_id);
        $this->assertCount(2, $report->dispositions);

        $this->actingAs($firstRtUser)->get(route('rt.reports.show', $report))->assertOk();
        $this->actingAs($secondRtUser)->get(route('rt.reports.show', $report))->assertOk()->assertSee('Terima Disposisi');
    }

    public function test_rw_can_escalate_to_kelurahan_and_other_rw_cannot_interfere(): void
    {
        [$rw, $rt] = $this->region();
        $report = $this->report($rt);
        $rtUser = $this->rtUser($rw, $rt);
        $rwUser = $this->rwUser($rw);
        $kelurahan = User::factory()->create([
            'role' => UserRole::KELURAHAN,
            'position' => VillagePosition::VILLAGE_SECRETARY,
        ]);
        [$otherRw] = $this->region('002');
        $otherRwUser = $this->rwUser($otherRw);

        $this->actingAs($rtUser)->post(route('rt.reports.forward', $report), [
            'target_level' => ReportHandlingLevel::RW->value,
            'reason' => 'Membutuhkan kewenangan lebih tinggi.',
        ]);

        $this->actingAs($otherRwUser)
            ->post(route('rw.reports.acknowledge', $report))
            ->assertForbidden();

        $this->actingAs($rwUser)->post(route('rw.reports.acknowledge', $report));
        $this->actingAs($rwUser)->post(route('rw.reports.forward', $report), [
            'target_level' => ReportHandlingLevel::KELURAHAN->value,
            'reason' => 'Membutuhkan kewenangan dan anggaran kelurahan.',
        ])->assertSessionHasNoErrors();

        $report->refresh();
        $this->assertSame(ReportHandlingLevel::KELURAHAN, $report->current_handling_level);
        $this->assertNull($report->current_rw_id);

        $this->actingAs($kelurahan)
            ->get(route('kelurahan.reports.show', $report))
            ->assertOk()
            ->assertSee('Terima Disposisi');
        $this->actingAs($kelurahan)
            ->post(route('kelurahan.reports.acknowledge', $report))
            ->assertSessionHasNoErrors();

        $this->assertSame(ReportStatus::PROCESSING, $report->fresh()->status);
    }

    /** @return array{0: Rw, 1: Rt, 2?: Rt} */
    private function region(string $rwCode = '001', bool $withSecondRt = false): array
    {
        $rw = Rw::query()->create(['code' => $rwCode, 'name' => "RW {$rwCode}"]);
        $first = Rt::query()->create(['rw_id' => $rw->id, 'code' => '001', 'name' => 'RT 001']);

        if (! $withSecondRt) {
            return [$rw, $first];
        }

        $second = Rt::query()->create(['rw_id' => $rw->id, 'code' => '002', 'name' => 'RT 002']);

        return [$rw, $first, $second];
    }

    private function rtUser(Rw $rw, Rt $rt): User
    {
        return User::factory()->create([
            'role' => UserRole::RT,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
        ]);
    }

    private function rwUser(Rw $rw): User
    {
        return User::factory()->create([
            'role' => UserRole::RW,
            'rw_id' => $rw->id,
            'rt_id' => null,
        ]);
    }

    private function report(Rt $rt): Report
    {
        return Report::factory()->create([
            'citizen_id' => Citizen::factory()->for($rt)->create()->id,
            'rt_id' => $rt->id,
        ]);
    }
}
