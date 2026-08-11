<?php

namespace App\Services;

use App\Context\CitizenRequestUnderstanding;
use App\Context\CreateCitizenReportCommand;
use App\Context\EntryContext;
use App\Context\ServiceEligibilityDecision;
use App\Context\ServiceRoutingDecision;
use App\Context\TrustedInboundEvent;
use App\Context\TrustedInboundProcessingResult;
use App\Enums\InboundRequestStatus;
use App\Enums\ServiceRouteTarget;
use App\Enums\TrustedInboundProcessingOutcome;
use App\Enums\TrustedInboundProcessingReason;
use App\Models\InboundRequest;
use App\Models\Report;
use App\Models\Rt;
use App\Support\PhoneNumberNormalizer;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Channel-neutral application boundary for already authenticated events.
 *
 * This is not a raw HTTP or webhook boundary. Channel authentication and
 * signature verification must happen before constructing the trusted event.
 */
final class ProcessTrustedInboundEvent
{
    private const MAX_MESSAGE_LENGTH = 4000;

    public function __construct(
        private readonly ReceiveInboundRequestService $receiveInboundRequestService,
        private readonly PhoneNumberNormalizer $phoneNumberNormalizer,
        private readonly CitizenRequestInterpreter $citizenRequestInterpreter,
        private readonly ServiceEligibilityPolicy $serviceEligibilityPolicy,
        private readonly ServiceRouter $serviceRouter,
        private readonly CreateCitizenReportService $createCitizenReportService,
    ) {}

    public function process(TrustedInboundEvent $event): TrustedInboundProcessingResult
    {
        $message = $this->validate($event);
        $inboundRequest = $this->receiveInboundRequestService->receive(
            source: $event->source->value,
            externalEventId: $event->externalEventId,
            receivedAt: $event->receivedAt,
        );

        $existingReport = $inboundRequest->report()->first();

        if ($inboundRequest->status === InboundRequestStatus::SUCCEEDED
            && $existingReport !== null) {
            return new TrustedInboundProcessingResult(
                inboundRequest: $inboundRequest,
                outcome: TrustedInboundProcessingOutcome::DUPLICATE_ALREADY_PROCESSED,
                reason: TrustedInboundProcessingReason::INBOUND_ALREADY_SUCCEEDED,
                report: $existingReport,
            );
        }

        $understanding = null;
        $eligibility = null;
        $routing = null;

        try {
            $understanding = $this->citizenRequestInterpreter->interpret(
                entry: new EntryContext(
                    channel: $event->source->value,
                    message: $message,
                    phone: $event->senderPhone,
                    rt: $event->entryRt,
                ),
                message: $message,
                incidentRt: $event->incidentRt,
            );
            $eligibility = $this->serviceEligibilityPolicy->evaluate($understanding);
            $routing = $this->serviceRouter->route($understanding);

            if (! $eligibility->isEligible() || ! $routing->canRoute()) {
                $this->recordServiceTarget($inboundRequest, $eligibility->routeTarget);

                return $this->result(
                    inboundRequest: $inboundRequest->fresh(),
                    outcome: TrustedInboundProcessingOutcome::BLOCKED,
                    reason: TrustedInboundProcessingReason::ELIGIBILITY_BLOCKED,
                    understanding: $understanding,
                    eligibility: $eligibility,
                    routing: $routing,
                );
            }

            if ($routing->target !== ServiceRouteTarget::REPORT_SERVICE) {
                $this->recordServiceTarget($inboundRequest, $routing->target);

                return $this->result(
                    inboundRequest: $inboundRequest->fresh(),
                    outcome: TrustedInboundProcessingOutcome::NON_REPORT_SERVICE,
                    reason: TrustedInboundProcessingReason::SERVICE_NOT_EXECUTED,
                    understanding: $understanding,
                    eligibility: $eligibility,
                    routing: $routing,
                );
            }

            $citizen = $understanding->serviceUnderstanding->contextResult->context->citizen;

            if ($citizen === null) {
                throw new DomainException('A routed report requires an existing citizen.');
            }

            $report = $this->createCitizenReportService->create(
                new CreateCitizenReportCommand(
                    requester: $citizen,
                    routingDecision: $routing,
                    title: 'Laporan Warga',
                    description: $message,
                    reportedAt: $event->receivedAt,
                    inboundRequest: $inboundRequest,
                ),
            );

            return $this->result(
                inboundRequest: $inboundRequest->fresh(),
                outcome: TrustedInboundProcessingOutcome::REPORT_CREATED,
                reason: TrustedInboundProcessingReason::REPORT_CREATED,
                understanding: $understanding,
                eligibility: $eligibility,
                routing: $routing,
                report: $report,
            );
        } catch (Throwable) {
            $completedInboundRequest = InboundRequest::query()
                ->with('report')
                ->find($inboundRequest->getKey());

            if ($completedInboundRequest?->status === InboundRequestStatus::SUCCEEDED
                && $completedInboundRequest->report !== null) {
                return new TrustedInboundProcessingResult(
                    inboundRequest: $completedInboundRequest,
                    outcome: TrustedInboundProcessingOutcome::DUPLICATE_ALREADY_PROCESSED,
                    reason: TrustedInboundProcessingReason::INBOUND_ALREADY_SUCCEEDED,
                    report: $completedInboundRequest->report,
                );
            }

            $this->recordFailure($inboundRequest, $routing?->target ?? $eligibility?->routeTarget);

            return $this->result(
                inboundRequest: $inboundRequest->fresh(),
                outcome: TrustedInboundProcessingOutcome::FAILED,
                reason: TrustedInboundProcessingReason::PROCESSING_EXCEPTION,
                understanding: $understanding,
                eligibility: $eligibility,
                routing: $routing,
            );
        }
    }

    private function validate(TrustedInboundEvent $event): string
    {
        $message = trim($event->message);

        if ($message === '') {
            throw new DomainException('Trusted inbound message is required.');
        }

        if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
            throw new DomainException('Trusted inbound message may not exceed 4000 characters.');
        }

        if (trim($event->externalEventId) === '' || mb_strlen($event->externalEventId) > 191) {
            throw new DomainException('A valid external event ID is required.');
        }

        if (mb_strlen($event->senderPhone) > 30
            || preg_match('/^62\d{8,13}$/', $this->phoneNumberNormalizer->normalize($event->senderPhone)) !== 1) {
            throw new DomainException('A valid trusted sender phone is required.');
        }

        $this->validateRt($event->entryRt);
        $this->validateRt($event->incidentRt);

        return $message;
    }

    private function validateRt(?Rt $rt): void
    {
        if ($rt === null) {
            return;
        }

        if (! $rt->exists || ! Rt::query()->whereKey($rt->getKey())->where('is_active', true)->exists()) {
            throw new DomainException('Trusted inbound territories must be persisted and active.');
        }
    }

    private function recordServiceTarget(
        InboundRequest $inboundRequest,
        ?ServiceRouteTarget $target,
    ): void {
        if ($target !== null) {
            $inboundRequest->update(['service_target' => $target]);
        }
    }

    private function recordFailure(
        InboundRequest $inboundRequest,
        ?ServiceRouteTarget $target,
    ): void {
        DB::transaction(function () use ($inboundRequest, $target): void {
            $locked = InboundRequest::query()->lockForUpdate()->find($inboundRequest->getKey());

            if ($locked === null
                || ($locked->status === InboundRequestStatus::SUCCEEDED
                    && $locked->report()->exists())) {
                return;
            }

            $locked->update([
                'status' => InboundRequestStatus::FAILED,
                'service_target' => $target,
                'attempt_count' => $locked->attempt_count + 1,
                'completed_at' => now(),
                'last_error_code' => 'TRUSTED_INBOUND_PROCESSING_FAILED',
            ]);
        }, 3);
    }

    private function result(
        InboundRequest $inboundRequest,
        TrustedInboundProcessingOutcome $outcome,
        TrustedInboundProcessingReason $reason,
        ?CitizenRequestUnderstanding $understanding = null,
        ?ServiceEligibilityDecision $eligibility = null,
        ?ServiceRoutingDecision $routing = null,
        ?Report $report = null,
    ): TrustedInboundProcessingResult {
        return new TrustedInboundProcessingResult(
            inboundRequest: $inboundRequest,
            outcome: $outcome,
            reason: $reason,
            understanding: $understanding,
            eligibilityDecision: $eligibility,
            routingDecision: $routing,
            report: $report,
        );
    }
}
