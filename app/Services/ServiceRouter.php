<?php

namespace App\Services;

use App\Context\CitizenRequestUnderstanding;
use App\Context\ServiceRoutingDecision;
use App\Enums\RoutingReadinessReason;
use App\Enums\ServiceEligibilityReason;
use App\Enums\ServiceRouteTarget;
use App\Enums\ServiceRoutingReason;
use App\Enums\ServiceRoutingStatus;

/**
 * Selects a service domain from existing understanding without executing it.
 */
final class ServiceRouter
{
    public function __construct(
        private readonly ServiceEligibilityPolicy $eligibilityPolicy,
    ) {}

    public function route(CitizenRequestUnderstanding $understanding): ServiceRoutingDecision
    {
        $intentResolution = $understanding->serviceUnderstanding->intentResolution;
        $territoryDecision = $understanding->serviceUnderstanding->serviceTerritoryDecision;
        $eligibility = $this->eligibilityPolicy->evaluate($understanding);

        if (! $eligibility->isEligible() || $eligibility->routeTarget === null) {
            return new ServiceRoutingDecision(
                status: ServiceRoutingStatus::BLOCKED,
                target: ServiceRouteTarget::MANUAL_CLARIFICATION,
                intent: $intentResolution->intent,
                urgency: $intentResolution->urgency,
                serviceTerritoryDecision: $territoryDecision,
                reason: $this->blockedReason($understanding, $eligibility->reason),
            );
        }

        return new ServiceRoutingDecision(
            status: ServiceRoutingStatus::ROUTABLE,
            target: $eligibility->routeTarget,
            intent: $intentResolution->intent,
            urgency: $intentResolution->urgency,
            serviceTerritoryDecision: $territoryDecision,
            reason: $this->routingReason($eligibility->routeTarget),
        );
    }

    private function blockedReason(
        CitizenRequestUnderstanding $understanding,
        ServiceEligibilityReason $reason,
    ): RoutingReadinessReason|ServiceEligibilityReason {
        return match ($reason) {
            ServiceEligibilityReason::IDENTITY_REQUIRED => RoutingReadinessReason::IDENTITY_REQUIRED,
            ServiceEligibilityReason::TERRITORY_REQUIRED => RoutingReadinessReason::TERRITORY_REQUIRED,
            ServiceEligibilityReason::IDENTITY_AND_TERRITORY_REQUIRED => RoutingReadinessReason::IDENTITY_AND_TERRITORY_REQUIRED,
            ServiceEligibilityReason::AUTHORIZATION_REQUIRED => ServiceEligibilityReason::AUTHORIZATION_REQUIRED,
            ServiceEligibilityReason::INVALID_INTENT_OR_ROUTING => $understanding->serviceUnderstanding->isIntentUrgencyValid()
                ? RoutingReadinessReason::INTENT_UNKNOWN
                : RoutingReadinessReason::INTENT_URGENCY_INVALID,
            ServiceEligibilityReason::ROUTING_NOT_READY,
            ServiceEligibilityReason::ELIGIBLE => $understanding->routingReadiness->reason,
        };
    }

    private function routingReason(ServiceRouteTarget $target): ServiceRoutingReason
    {
        return match ($target) {
            ServiceRouteTarget::REPORT_SERVICE => ServiceRoutingReason::ROUTED_TO_REPORT,
            ServiceRouteTarget::EMERGENCY_SERVICE => ServiceRoutingReason::ROUTED_TO_EMERGENCY,
            ServiceRouteTarget::LETTER_SERVICE => ServiceRoutingReason::ROUTED_TO_LETTER,
            ServiceRouteTarget::INFORMATION_SERVICE => ServiceRoutingReason::ROUTED_TO_INFORMATION,
            ServiceRouteTarget::ASPIRATION_SERVICE => ServiceRoutingReason::ROUTED_TO_ASPIRATION,
            ServiceRouteTarget::MANUAL_CLARIFICATION => throw new \LogicException(
                'Manual clarification cannot be a routable operational service.',
            ),
        };
    }
}
