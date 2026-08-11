<?php

namespace Tests\Feature;

use App\Context\TrustedInboundEvent;
use App\Context\TrustedInboundProcessingResult;
use App\Enums\InboundProcessingReason;
use App\Enums\InboundRequestStatus;
use App\Enums\InboundSource;
use App\Enums\ServiceEligibilityReason;
use App\Enums\ServiceRouteTarget;
use App\Enums\TrustedInboundProcessingOutcome;
use App\Models\Citizen;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\CitizenRequestInterpreter;
use App\Services\InboundRequestLifecyclePolicy;
use App\Services\ProcessTrustedInboundEvent;
use App\Services\ReceiveInboundRequestService;
use DateTimeImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProcessTrustedInboundEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_citizen_report_creates_linked_succeeded_report(): void
    {
        $rt = $this->createRt('001', '001');
        $citizen = $this->createCitizen($rt);

        $result = $this->process($this->event(
            externalEventId: 'event-report-001',
            phone: $citizen->phone_normalized,
            message: 'jalan rusak',
            incidentRt: $rt,
        ));

        $this->assertSame(TrustedInboundProcessingOutcome::REPORT_CREATED, $result->outcome);
        $this->assertSame(InboundRequestStatus::SUCCEEDED, $result->inboundRequest->status);
        $this->assertSame(ServiceRouteTarget::REPORT_SERVICE, $result->routingDecision?->target);
        $this->assertSame('Laporan Warga', $result->report?->title);
        $this->assertSame('jalan rusak', $result->report?->description);
        $this->assertSame($result->inboundRequest->id, $result->report?->inbound_request_id);
    }

    public function test_duplicate_succeeded_event_returns_same_report_without_new_domain_writes(): void
    {
        $rt = $this->createRt('001', '001');
        $citizen = $this->createCitizen($rt);
        $event = $this->event('event-report-001', $citizen->phone_normalized, 'jalan rusak', incidentRt: $rt);

        $first = $this->process($event);
        $duplicate = $this->process($event);

        $this->assertSame(TrustedInboundProcessingOutcome::DUPLICATE_ALREADY_PROCESSED, $duplicate->outcome);
        $this->assertSame($first->inboundRequest->id, $duplicate->inboundRequest->id);
        $this->assertSame($first->inboundRequest->correlation_id, $duplicate->inboundRequest->correlation_id);
        $this->assertSame($first->report?->id, $duplicate->report?->id);
        $this->assertNull($duplicate->understanding);
        $this->assertDatabaseCount('reports', 1);
        $this->assertDatabaseCount('report_histories', 1);
        $this->assertDatabaseCount('report_ticket_sequences', 1);
    }

    public function test_unknown_citizen_report_is_blocked_without_report(): void
    {
        $incidentRt = $this->createRt('001', '001');

        $result = $this->process($this->event(
            'event-unknown-report',
            '6289999999999',
            'jalan rusak',
            incidentRt: $incidentRt,
        ));

        $this->assertSame(TrustedInboundProcessingOutcome::BLOCKED, $result->outcome);
        $this->assertSame(ServiceEligibilityReason::IDENTITY_REQUIRED, $result->eligibilityDecision?->reason);
        $this->assertSame(InboundRequestStatus::BLOCKED, $result->inboundRequest->status);
        $this->assertSame(ServiceRouteTarget::REPORT_SERVICE, $result->inboundRequest->service_target);
        $this->assertSame(InboundProcessingReason::IDENTITY_REQUIRED, $result->inboundRequest->processing_reason);
        $this->assertDatabaseCount('reports', 0);
        $this->assertDatabaseCount('citizens', 0);
    }

    public function test_public_information_for_unknown_citizen_stops_before_execution(): void
    {
        $result = $this->process($this->event(
            'event-information',
            '6289999999999',
            'nomor ambulans desa berapa',
        ));

        $this->assertSame(TrustedInboundProcessingOutcome::NON_REPORT_SERVICE, $result->outcome);
        $this->assertSame(ServiceRouteTarget::INFORMATION_SERVICE, $result->routingDecision?->target);
        $this->assertSame(InboundRequestStatus::PENDING_ACTION, $result->inboundRequest->status);
        $this->assertSame(InboundProcessingReason::PENDING_SERVICE_ACTION, $result->inboundRequest->processing_reason);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_emergency_is_preserved_but_not_dispatched_or_created_as_report(): void
    {
        $incidentRt = $this->createRt('001', '001');

        $result = $this->process($this->event(
            'event-emergency',
            '6289999999999',
            'tolong ambulans, ada orang pingsan',
            incidentRt: $incidentRt,
        ));

        $this->assertSame(TrustedInboundProcessingOutcome::NON_REPORT_SERVICE, $result->outcome);
        $this->assertSame(ServiceRouteTarget::EMERGENCY_SERVICE, $result->routingDecision?->target);
        $this->assertSame(ServiceRouteTarget::EMERGENCY_SERVICE, $result->inboundRequest->service_target);
        $this->assertSame(InboundRequestStatus::PENDING_ACTION, $result->inboundRequest->status);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_letter_stops_before_execution(): void
    {
        $rt = $this->createRt('001', '001');
        $citizen = $this->createCitizen($rt);

        $result = $this->process($this->event(
            'event-letter',
            $citizen->phone_normalized,
            'buatkan surat domisili',
        ));

        $this->assertSame(TrustedInboundProcessingOutcome::NON_REPORT_SERVICE, $result->outcome);
        $this->assertSame(ServiceRouteTarget::LETTER_SERVICE, $result->routingDecision?->target);
        $this->assertSame(InboundRequestStatus::PENDING_ACTION, $result->inboundRequest->status);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_duplicate_blocked_request_short_circuits_without_reinterpretation(): void
    {
        $incidentRt = $this->createRt('001', '001');
        $event = $this->event(
            'event-blocked-duplicate',
            '6289999999999',
            'jalan rusak',
            incidentRt: $incidentRt,
        );
        $first = $this->process($event);

        $this->mock(CitizenRequestInterpreter::class)->shouldNotReceive('interpret');
        $duplicate = $this->process($event);

        $this->assertSame(TrustedInboundProcessingOutcome::BLOCKED, $duplicate->outcome);
        $this->assertSame(InboundRequestStatus::BLOCKED, $duplicate->inboundRequest->status);
        $this->assertSame($first->inboundRequest->correlation_id, $duplicate->inboundRequest->correlation_id);
        $this->assertSame(InboundProcessingReason::IDENTITY_REQUIRED, $duplicate->inboundRequest->processing_reason);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_duplicate_pending_action_short_circuits_without_reinterpretation(): void
    {
        $event = $this->event(
            'event-information-duplicate',
            '6289999999999',
            'nomor ambulans desa berapa',
        );
        $first = $this->process($event);

        $this->mock(CitizenRequestInterpreter::class)->shouldNotReceive('interpret');
        $duplicate = $this->process($event);

        $this->assertSame(TrustedInboundProcessingOutcome::NON_REPORT_SERVICE, $duplicate->outcome);
        $this->assertSame(InboundRequestStatus::PENDING_ACTION, $duplicate->inboundRequest->status);
        $this->assertSame($first->inboundRequest->correlation_id, $duplicate->inboundRequest->correlation_id);
        $this->assertSame(ServiceRouteTarget::INFORMATION_SERVICE, $duplicate->inboundRequest->service_target);
    }

    public function test_aspiration_waits_for_its_unimplemented_service(): void
    {
        $rt = $this->createRt('001', '001');
        $citizen = $this->createCitizen($rt);

        $result = $this->process($this->event(
            'event-aspiration',
            $citizen->phone_normalized,
            'saya usul dibuatkan lampu jalan',
        ));

        $this->assertSame(TrustedInboundProcessingOutcome::NON_REPORT_SERVICE, $result->outcome);
        $this->assertSame(InboundRequestStatus::PENDING_ACTION, $result->inboundRequest->status);
        $this->assertSame(ServiceRouteTarget::ASPIRATION_SERVICE, $result->inboundRequest->service_target);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_processing_duplicate_does_not_start_second_execution(): void
    {
        $event = $this->event('event-processing', '6281234567890', 'jalan rusak');
        $inbound = app(ReceiveInboundRequestService::class)->receive(
            $event->source->value,
            $event->externalEventId,
            $event->receivedAt,
        );
        $inbound->update([
            'status' => InboundRequestStatus::PROCESSING,
            'attempt_count' => 1,
            'processing_started_at' => now(),
        ]);

        $this->mock(CitizenRequestInterpreter::class)->shouldNotReceive('interpret');
        $result = $this->process($event);

        $this->assertSame(TrustedInboundProcessingOutcome::PROCESSING_IN_PROGRESS, $result->outcome);
        $this->assertSame(InboundRequestStatus::PROCESSING, $result->inboundRequest->status);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_invalid_lifecycle_transition_is_rejected(): void
    {
        $this->expectException(DomainException::class);

        app(InboundRequestLifecyclePolicy::class)->assertCanTransition(
            InboundRequestStatus::BLOCKED,
            InboundRequestStatus::PROCESSING,
        );
    }

    public function test_cross_territory_report_preserves_citizen_domicile(): void
    {
        $domicileRt = $this->createRt('001', '001');
        $incidentRt = $this->createRt('005', '010');
        $citizen = $this->createCitizen($domicileRt);

        $result = $this->process($this->event(
            'event-cross-territory',
            $citizen->phone_normalized,
            'jalan depan rumah rusak',
            entryRt: $incidentRt,
            incidentRt: $incidentRt,
        ));

        $this->assertSame(TrustedInboundProcessingOutcome::REPORT_CREATED, $result->outcome);
        $this->assertSame($incidentRt->id, $result->report?->rt_id);
        $this->assertSame($domicileRt->id, $citizen->fresh()->rt_id);
    }

    public function test_empty_message_is_rejected_before_receipt(): void
    {
        $this->expectException(DomainException::class);

        try {
            $this->process($this->event('event-empty', '6281234567890', '   '));
        } finally {
            $this->assertDatabaseCount('inbound_requests', 0);
        }
    }

    public function test_oversized_message_is_rejected_before_receipt(): void
    {
        $this->expectException(DomainException::class);

        try {
            $this->process($this->event('event-large', '6281234567890', str_repeat('a', 4001)));
        } finally {
            $this->assertDatabaseCount('inbound_requests', 0);
        }
    }

    public function test_processing_exception_records_only_safe_failure_code(): void
    {
        $this->mock(CitizenRequestInterpreter::class)
            ->shouldReceive('interpret')
            ->once()
            ->andThrow(new RuntimeException('secret provider detail'));

        $result = $this->process($this->event('event-failure', '6281234567890', 'jalan rusak'));

        $this->assertSame(TrustedInboundProcessingOutcome::FAILED, $result->outcome);
        $this->assertSame(InboundRequestStatus::FAILED, $result->inboundRequest->status);
        $this->assertSame('TRUSTED_INBOUND_PROCESSING_FAILED', $result->inboundRequest->last_error_code);
        $this->assertStringNotContainsString('secret provider detail', $result->inboundRequest->last_error_code);
        $this->assertDatabaseCount('reports', 0);
        $this->assertDatabaseCount('report_histories', 0);
    }

    public function test_failed_request_is_not_automatically_retried_on_duplicate(): void
    {
        $event = $this->event('event-failed-duplicate', '6281234567890', 'jalan rusak');
        $interpreter = $this->mock(CitizenRequestInterpreter::class);
        $interpreter->shouldReceive('interpret')
            ->once()
            ->andThrow(new RuntimeException('technical failure'));

        $first = $this->process($event);
        $duplicate = $this->process($event);

        $this->assertSame(TrustedInboundProcessingOutcome::FAILED, $duplicate->outcome);
        $this->assertSame(InboundRequestStatus::FAILED, $duplicate->inboundRequest->status);
        $this->assertSame($first->inboundRequest->correlation_id, $duplicate->inboundRequest->correlation_id);
        $this->assertSame(1, $duplicate->inboundRequest->attempt_count);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_inbound_receipt_does_not_persist_sender_phone_or_message(): void
    {
        $result = $this->process($this->event(
            'event-private',
            '6289999999999',
            'nomor ambulans desa berapa',
        ));
        $row = $result->inboundRequest->getAttributes();

        $this->assertNotContains('6289999999999', $row, true);
        $this->assertNotContains('nomor ambulans desa berapa', $row, true);
    }

    private function process(TrustedInboundEvent $event): TrustedInboundProcessingResult
    {
        return app(ProcessTrustedInboundEvent::class)->process($event);
    }

    private function event(
        string $externalEventId,
        string $phone,
        string $message,
        ?Rt $entryRt = null,
        ?Rt $incidentRt = null,
    ): TrustedInboundEvent {
        return new TrustedInboundEvent(
            source: InboundSource::WEB_TEST,
            externalEventId: $externalEventId,
            senderPhone: $phone,
            message: $message,
            receivedAt: new DateTimeImmutable('2026-08-11 10:00:00'),
            entryRt: $entryRt,
            incidentRt: $incidentRt,
        );
    }

    private function createCitizen(Rt $rt): Citizen
    {
        return Citizen::factory()->for($rt)->create([
            'phone' => '081234567890',
            'phone_normalized' => '6281234567890',
        ]);
    }

    private function createRt(string $rwCode, string $rtCode): Rt
    {
        $rw = Rw::query()->create(['code' => $rwCode, 'name' => "RW {$rwCode}"]);

        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => $rtCode,
            'name' => "RT {$rtCode}",
        ]);
    }
}
