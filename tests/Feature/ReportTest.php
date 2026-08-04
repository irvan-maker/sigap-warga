<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\TicketNumberGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_belongs_to_citizen(): void
    {
        [$rt, $citizen] = $this->createCitizen();
        $report = $this->createReport($citizen, $rt);

        $this->assertTrue($report->citizen->is($citizen));
    }

    public function test_report_belongs_to_rt(): void
    {
        [$rt, $citizen] = $this->createCitizen();
        $report = $this->createReport($citizen, $rt);

        $this->assertTrue($report->rt->is($rt));
    }

    public function test_citizen_has_many_reports(): void
    {
        [$rt, $citizen] = $this->createCitizen();
        Report::factory()->count(2)->create([
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
        ]);

        $this->assertCount(2, $citizen->reports);
    }

    public function test_rt_has_many_reports(): void
    {
        [$rt, $citizen] = $this->createCitizen();
        Report::factory()->count(2)->create([
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
        ]);

        $this->assertCount(2, $rt->reports);
    }

    public function test_default_status_is_new(): void
    {
        [$rt, $citizen] = $this->createCitizen();
        $report = $this->createReport($citizen, $rt);

        $this->assertSame(ReportStatus::NEW, $report->status);
    }

    public function test_ticket_number_follows_required_format(): void
    {
        $ticket = app(TicketNumberGenerator::class)->generate();

        $this->assertMatchesRegularExpression(
            '/^SGW-'.now()->format('Y').'-\d{5}$/',
            $ticket,
        );
    }

    public function test_generated_ticket_numbers_are_unique(): void
    {
        $generator = app(TicketNumberGenerator::class);

        $tickets = collect(range(1, 10))
            ->map(fn (): string => $generator->generate());

        $this->assertCount(10, $tickets->unique());
    }

    public function test_citizen_and_report_rt_must_match(): void
    {
        $rw = $this->createRw();
        $citizenRt = $this->createRt($rw, '001');
        $reportRt = $this->createRt($rw, '002');
        $citizen = Citizen::factory()->for($citizenRt)->create();

        $this->expectException(LogicException::class);

        $this->createReport($citizen, $reportRt);
    }

    public function test_deleting_a_referenced_citizen_is_restricted(): void
    {
        [$rt, $citizen] = $this->createCitizen();
        $this->createReport($citizen, $rt);

        $this->expectException(QueryException::class);

        $citizen->delete();
    }

    public function test_deleting_a_referenced_rt_is_restricted(): void
    {
        [$rt, $citizen] = $this->createCitizen();
        $this->createReport($citizen, $rt);

        $this->expectException(QueryException::class);

        $rt->delete();
    }

    /**
     * @return array{Rt, Citizen}
     */
    private function createCitizen(): array
    {
        $rt = $this->createRt();

        return [$rt, Citizen::factory()->for($rt)->create()];
    }

    private function createReport(Citizen $citizen, Rt $rt): Report
    {
        return Report::factory()->create([
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
        ]);
    }

    private function createRw(string $code = '001'): Rw
    {
        return Rw::query()->create([
            'code' => $code,
            'name' => "RW {$code}",
        ]);
    }

    private function createRt(?Rw $rw = null, string $code = '001'): Rt
    {
        $rw ??= $this->createRw();

        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => $code,
            'name' => "RT {$code}",
        ]);
    }
}
