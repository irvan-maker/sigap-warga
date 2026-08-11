<?php

namespace App\Services;

use App\Context\CitizenRequestUnderstanding;
use App\Context\CreateCitizenReportCommand;
use App\Context\EntryContext;
use App\Context\ServiceEligibilityDecision;
use App\Context\ServiceRoutingDecision;
use App\Context\TrustedInboundEvent;
use App\Context\TrustedInboundProcessingResult;
use App\Enums\InboundProcessingReason;
use App\Enums\InboundRequestStatus;
use App\Enums\ServiceEligibilityReason;
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
        private readonly InboundRequestLifecyclePolicy $lifecyclePolicy,
    ) {}

    public function process(TrustedInboundEvent $event): TrustedInboundProcessingResult
    {
        $message = $this->validate($event);
        $inboundRequest = $this->receiveInboundRequestService->receive(
            source: $event->source->value,
            externalEventId: $event->externalEventId,
            receivedAt: $event->receivedAt,
        );

        $shortCircuit = $this->claimOrShortCircuit($inboundRequest);

        if ($shortCircuit !== null) {
            return $shortCircuit;
        }

        $inboundRequest->refresh();

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
                $this->completeBlocked($inboundRequest, $eligibility);

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
                $this->completePendingAction($inboundRequest, $routing->target);

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

            if ($completedInboundRequest !== null
                && ! in_array($completedInboundRequest->status, [
                    InboundRequestStatus::RECEIVED,
                    InboundRequestStatus::PROCESSING,
                ], true)) {
                return $this->durableResult($completedInboundRequest);
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

    private function claimOrShortCircuit(
        InboundRequest $inboundRequest,
    ): ?TrustedInboundProcessingResult {
        return DB::transaction(function () use ($inboundRequest): ?TrustedInboundProcessingResult {
            $locked = InboundRequest::query()
                ->with('report')
                ->lockForUpdate()
                ->findOrFail($inboundRequest->getKey());

            if ($locked->status !== InboundRequestStatus::RECEIVED) {
                return $this->durableResult($locked);
            }

            $this->lifecyclePolicy->assertCanTransition(
                $locked->status,
                InboundRequestStatus::PROCESSING,
            );

            $claimed = InboundRequest::query()
                ->whereKey($locked->getKey())
                ->where('status', InboundRequestStatus::RECEIVED->value)
                ->update([
                    'status' => InboundRequestStatus::PROCESSING->value,
                    'attempt_count' => $locked->attempt_count + 1,
                    'processing_started_at' => now(),
                    'completed_at' => null,
                    'processing_reason' => null,
                    'last_error_code' => null,
                    'updated_at' => now(),
                ]);

            if ($claimed === 1) {
                return null;
            }

            return $this->durableResult(
                InboundRequest::query()->with('report')->findOrFail($locked->getKey()),
            );
        }, 3);
    }

    private function durableResult(InboundRequest $inboundRequest): TrustedInboundProcessingResult
    {
        return match ($inboundRequest->status) {
            InboundRequestStatus::SUCCEEDED => new TrustedInboundProcessingResult(
                inboundRequest: $inboundRequest,
                outcome: TrustedInboundProcessingOutcome::DUPLICATE_ALREADY_PROCESSED,
                reason: TrustedInboundProcessingReason::INBOUND_ALREADY_SUCCEEDED,
                report: $inboundRequest->report,
            ),
            InboundRequestStatus::BLOCKED => new TrustedInboundProcessingResult(
                inboundRequest: $inboundRequest,
                outcome: TrustedInboundProcessingOutcome::BLOCKED,
                reason: TrustedInboundProcessingReason::ELIGIBILITY_BLOCKED,
            ),
            InboundRequestStatus::PENDING_ACTION => new TrustedInboundProcessingResult(
                inboundRequest: $inboundRequest,
                outcome: TrustedInboundProcessingOutcome::NON_REPORT_SERVICE,
                reason: TrustedInboundProcessingReason::SERVICE_NOT_EXECUTED,
            ),
            InboundRequestStatus::FAILED => new TrustedInboundProcessingResult(
                inboundRequest: $inboundRequest,
                outcome: TrustedInboundProcessingOutcome::FAILED,
                reason: TrustedInboundProcessingReason::PROCESSING_EXCEPTION,
            ),
            InboundRequestStatus::PROCESSING => new TrustedInboundProcessingResult(
                inboundRequest: $inboundRequest,
                outcome: TrustedInboundProcessingOutcome::PROCESSING_IN_PROGRESS,
                reason: TrustedInboundProcessingReason::INBOUND_PROCESSING,
            ),
            InboundRequestStatus::RECEIVED => throw new DomainException(
                'A received inbound request must be claimed before processing.',
            ),
        };
    }

    private function completeBlocked(
        InboundRequest $inboundRequest,
        ServiceEligibilityDecision $eligibility,
    ): void {
        $this->complete(
            inboundRequest: $inboundRequest,
            status: InboundRequestStatus::BLOCKED,
            target: $eligibility->routeTarget,
            reason: $this->blockedReason($eligibility->reason),
        );
    }

    private function completePendingAction(
        InboundRequest $inboundRequest,
        ServiceRouteTarget $target,
    ): void {
        $this->complete(
            inboundRequest: $inboundRequest,
            status: InboundRequestStatus::PENDING_ACTION,
            target: $target,
            reason: InboundProcessingReason::PENDING_SERVICE_ACTION,
        );
    }

    private function complete(
        InboundRequest $inboundRequest,
        InboundRequestStatus $status,
        ?ServiceRouteTarget $target,
        InboundProcessingReason $reason,
    ): void {
        DB::transaction(function () use ($inboundRequest, $status, $target, $reason): void {
            $locked = InboundRequest::query()->lockForUpdate()->findOrFail($inboundRequest->getKey());

            $this->lifecyclePolicy->assertCanTransition($locked->status, $status);
            $locked->update([
                'status' => $status,
                'service_target' => $target,
                'processing_reason' => $reason,
                'completed_at' => now(),
                'last_error_code' => null,
            ]);
        }, 3);
    }

    private function blockedReason(ServiceEligibilityReason $reason): InboundProcessingReason
    {
        return match ($reason) {
            ServiceEligibilityReason::IDENTITY_REQUIRED => InboundProcessingReason::IDENTITY_REQUIRED,
            ServiceEligibilityReason::TERRITORY_REQUIRED => InboundProcessingReason::TERRITORY_REQUIRED,
            ServiceEligibilityReason::IDENTITY_AND_TERRITORY_REQUIRED => InboundProcessingReason::IDENTITY_AND_TERRITORY_REQUIRED,
            ServiceEligibilityReason::AUTHORIZATION_REQUIRED => InboundProcessingReason::AUTHORIZATION_REQUIRED,
            ServiceEligibilityReason::INVALID_INTENT_OR_ROUTING => InboundProcessingReason::INVALID_INTENT_OR_ROUTING,
            ServiceEligibilityReason::ROUTING_NOT_READY,
            ServiceEligibilityReason::ELIGIBLE => InboundProcessingReason::ROUTING_NOT_READY,
        };
    }

    private function recordFailure(
        InboundRequest $inboundRequest,
        ?ServiceRouteTarget $target,
    ): void {
        DB::transaction(function () use ($inboundRequest, $target): void {
            $locked = InboundRequest::query()->lockForUpdate()->find($inboundRequest->getKey());

            if ($locked === null || $locked->status !== InboundRequestStatus::PROCESSING) {
                return;
            }

            $this->lifecyclePolicy->assertCanTransition(
                $locked->status,
                InboundRequestStatus::FAILED,
            );
            $locked->update([
                'status' => InboundRequestStatus::FAILED,
                'service_target' => $target,
                'processing_reason' => null,
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
