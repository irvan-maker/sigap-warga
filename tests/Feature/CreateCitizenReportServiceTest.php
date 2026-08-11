<?php

namespace Tests\Feature;

use App\Context\CreateCitizenReportCommand;
use App\Context\EntryContext;
use App\Context\ServiceRoutingDecision;
use App\Context\ServiceTerritoryDecision;
use App\Enums\CitizenIntent;
use App\Enums\InboundRequestStatus;
use App\Enums\ReportStatus;
use App\Enums\ServiceRouteTarget;
use App\Enums\ServiceRoutingReason;
use App\Enums\ServiceRoutingStatus;
use App\Enums\ServiceTerritoryStatus;
use App\Enums\TerritoryPurpose;
use App\Enums\UrgencyLevel;
use App\Models\Citizen;
use App\Models\InboundRequest;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\CitizenRequestInterpreter;
use App\Services\CreateCitizenReportService;
use App\Services\ReceiveInboundRequestService;
use App\Services\ServiceRouter;
use DateTimeImmutable;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateCitizenReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_citizen_can_create_normal_report_for_service_territory(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $citizen = $this->createCitizen($rt);

        $report = $this->create($this->command($citizen, $rt, UrgencyLevel::NORMAL));

        $this->assertSame($citizen->id, $report->citizen_id);
        $this->assertSame($rt->id, $report->rt_id);
        $this->assertSame(ReportStatus::NEW, $report->status);
        $this->assertNotNull($report->inbound_request_id);
        $this->assertSame(InboundRequestStatus::SUCCEEDED, $report->inboundRequest->status);
    }

    public function test_high_urgency_remains_a_report_not_an_emergency(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $citizen = $this->createCitizen($rt);

        $report = $this->create($this->command($citizen, $rt, UrgencyLevel::HIGH));

        $this->assertInstanceOf(Report::class, $report);
        $this->assertSame($rt->id, $report->rt_id);
        $this->assertDatabaseCount('reports', 1);
    }

    public function test_emergency_is_rejected_before_any_report_or_ticket_write(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $citizen = $this->createCitizen($rt);
        $command = $this->command(
            citizen: $citizen,
            serviceRt: $rt,
            urgency: UrgencyLevel::EMERGENCY,
            intent: CitizenIntent::EMERGENCY,
            target: ServiceRouteTarget::EMERGENCY_SERVICE,
        );

        try {
            $this->create($command);
            $this->fail('Emergency routing must not enter Report execution.');
        } catch (DomainException) {
            $this->assertDatabaseCount('reports', 0);
            $this->assertDatabaseCount('report_ticket_sequences', 0);
        }
    }

    public function test_unsaved_unknown_citizen_is_rejected_without_creating_citizen(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $unknownCitizen = new Citizen([
            'name' => 'Unknown Caller',
            'phone' => '081299999999',
            'phone_normalized' => '6281299999999',
        ]);

        try {
            $this->create($this->command($unknownCitizen, $rt, UrgencyLevel::NORMAL));
            $this->fail('An unsaved citizen must not enter Report execution.');
        } catch (DomainException) {
            $this->assertDatabaseCount('citizens', 0);
            $this->assertDatabaseCount('reports', 0);
            $this->assertDatabaseCount('report_ticket_sequences', 0);
        }
    }

    public function test_execution_does_not_change_citizen_identity_or_domicile(): void
    {
        [$domicileRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($domicileRt);
        $citizenBefore = DB::table('citizens')->find($citizen->id);

        $this->create($this->command($citizen, $incidentRt, UrgencyLevel::NORMAL));

        $this->assertEquals($citizenBefore, DB::table('citizens')->find($citizen->id));
        $this->assertSame($domicileRt->id, $citizen->fresh()->rt_id);
    }

    public function test_existing_ticket_generator_and_initial_history_are_reused(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $citizen = $this->createCitizen($rt);

        $report = $this->create($this->command($citizen, $rt, UrgencyLevel::NORMAL));

        $this->assertMatchesRegularExpression('/^SGW-'.now()->format('Y').'-\d{5}$/', $report->ticket_number);
        $this->assertDatabaseHas('report_ticket_sequences', [
            'year' => (int) now()->format('Y'),
            'last_number' => 1,
        ]);
        $this->assertDatabaseHas('report_histories', [
            'report_id' => $report->id,
            'old_status' => null,
            'new_status' => ReportStatus::NEW->value,
        ]);
    }

    public function test_cross_territory_report_uses_incident_rt_without_changing_domicile(): void
    {
        [$domicileRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($domicileRt);

        $report = $this->create($this->command($citizen, $incidentRt, UrgencyLevel::NORMAL));

        $this->assertSame($incidentRt->id, $report->rt_id);
        $this->assertTrue($report->rt->is($incidentRt));
        $this->assertTrue($report->citizen->rt->is($domicileRt));
        $this->assertSame($domicileRt->id, $citizen->fresh()->rt_id);
    }

    public function test_actual_understanding_and_routing_decision_can_execute_report(): void
    {
        [$domicileRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($domicileRt);
        $message = 'jalan depan rumah rusak';
        $understanding = app(CitizenRequestInterpreter::class)->interpret(
            new EntryContext('trusted-test-adapter', $message, $citizen->phone_normalized),
            $message,
            $incidentRt,
        );
        $routing = app(ServiceRouter::class)->route($understanding);
        $command = new CreateCitizenReportCommand(
            requester: $citizen,
            routingDecision: $routing,
            title: 'Jalan lingkungan rusak',
            description: 'Permukaan jalan rusak dan perlu diperbaiki.',
            reportedAt: new DateTimeImmutable,
            inboundRequest: $this->receiveInbound('pipeline-event-001'),
        );

        $report = $this->create($command);

        $this->assertSame($incidentRt->id, $report->rt_id);
        $this->assertSame($domicileRt->id, $report->citizen->rt_id);
        $this->assertSame(ReportStatus::NEW, $report->status);
    }

    private function create(CreateCitizenReportCommand $command): Report
    {
        return app(CreateCitizenReportService::class)->create($command);
    }

    private function command(
        Citizen $citizen,
        Rt $serviceRt,
        UrgencyLevel $urgency,
        CitizenIntent $intent = CitizenIntent::REPORT,
        ServiceRouteTarget $target = ServiceRouteTarget::REPORT_SERVICE,
    ): CreateCitizenReportCommand {
        return new CreateCitizenReportCommand(
            requester: $citizen,
            routingDecision: new ServiceRoutingDecision(
                status: ServiceRoutingStatus::ROUTABLE,
                target: $target,
                intent: $intent,
                urgency: $urgency,
                serviceTerritoryDecision: new ServiceTerritoryDecision(
                    intent: $intent,
                    status: ServiceTerritoryStatus::RESOLVED,
                    preferredRt: $serviceRt,
                    preferredSource: TerritoryPurpose::INCIDENT,
                ),
                reason: $target === ServiceRouteTarget::REPORT_SERVICE
                    ? ServiceRoutingReason::ROUTED_TO_REPORT
                    : ServiceRoutingReason::ROUTED_TO_EMERGENCY,
            ),
            title: 'Jalan lingkungan rusak',
            description: 'Permukaan jalan rusak dan perlu diperbaiki.',
            reportedAt: new DateTimeImmutable,
            inboundRequest: $this->receiveInbound('request-001'),
        );
    }

    public function test_one_inbound_request_cannot_create_two_reports(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $citizen = $this->createCitizen($rt);
        $command = $this->command($citizen, $rt, UrgencyLevel::NORMAL);

        $this->create($command);

        $this->expectException(DomainException::class);

        try {
            $this->create($command);
        } finally {
            $this->assertDatabaseCount('reports', 1);
            $this->assertDatabaseCount('report_histories', 1);
        }
    }

    public function test_cross_territory_report_keeps_inbound_trace(): void
    {
        [$domicileRt, $incidentRt] = $this->createDifferentTerritories();
        $citizen = $this->createCitizen($domicileRt);

        $report = $this->create($this->command($citizen, $incidentRt, UrgencyLevel::NORMAL));

        $this->assertNotNull($report->inbound_request_id);
        $this->assertSame($incidentRt->id, $report->rt_id);
        $this->assertSame($domicileRt->id, $citizen->fresh()->rt_id);
    }

    public function test_report_creation_failure_leaves_no_partial_domain_writes_or_link(): void
    {
        $rt = $this->createRt($this->createRw('001'), '001');
        $citizen = $this->createCitizen($rt);
        $command = $this->command($citizen, $rt, UrgencyLevel::NORMAL);
        $inboundId = $command->inboundRequest->id;
        DB::table('citizens')->where('id', $citizen->id)->delete();

        try {
            $this->create($command);
            $this->fail('The report insert must fail when its citizen no longer exists.');
        } catch (QueryException) {
            $this->assertDatabaseCount('reports', 0);
            $this->assertDatabaseCount('report_histories', 0);
            $this->assertDatabaseHas('inbound_requests', [
                'id' => $inboundId,
                'status' => InboundRequestStatus::RECEIVED->value,
                'service_target' => null,
                'attempt_count' => 0,
            ]);
        }
    }

    private function receiveInbound(string $externalEventId): InboundRequest
    {
        return app(ReceiveInboundRequestService::class)->receive(
            source: 'trusted-test-adapter',
            externalEventId: $externalEventId,
        );
    }

    private function createCitizen(Rt $rt): Citizen
    {
        return Citizen::factory()->for($rt)->create([
            'name' => 'Warga Tetap',
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
    }

    /** @return array{Rt, Rt} */
    private function createDifferentTerritories(): array
    {
        return [
            $this->createRt($this->createRw('001'), '001'),
            $this->createRt($this->createRw('005'), '010'),
        ];
    }

    private function createRw(string $code): Rw
    {
        return Rw::query()->create(['code' => $code, 'name' => "RW {$code}"]);
    }

    private function createRt(Rw $rw, string $code): Rt
    {
        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => $code,
            'name' => "RT {$code}",
        ]);
    }
}
