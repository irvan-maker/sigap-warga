<?php

namespace App\Services;

use App\Context\CitizenRequestUnderstanding;
use App\Context\ServiceRoutingDecision;
use App\Enums\CitizenIntent;
use App\Enums\RoutingReadinessReason;
use App\Enums\ServiceRouteTarget;
use App\Enums\ServiceRoutingReason;
use App\Enums\ServiceRoutingStatus;

/**
 * Selects a service domain from existing understanding without executing it.
 */
final class ServiceRouter
{
    public function route(CitizenRequestUnderstanding $understanding): ServiceRoutingDecision
    {
        $intentResolution = $understanding->serviceUnderstanding->intentResolution;
        $territoryDecision = $understanding->serviceUnderstanding->serviceTerritoryDecision;

        if (! $understanding->routingReadiness->canProceed()) {
            return new ServiceRoutingDecision(
                status: ServiceRoutingStatus::BLOCKED,
                target: ServiceRouteTarget::MANUAL_CLARIFICATION,
                intent: $intentResolution->intent,
                urgency: $intentResolution->urgency,
                serviceTerritoryDecision: $territoryDecision,
                reason: $understanding->routingReadiness->reason,
            );
        }

        $route = $this->routeFor($intentResolution->intent);

        if ($route === null) {
            return new ServiceRoutingDecision(
                status: ServiceRoutingStatus::BLOCKED,
                target: ServiceRouteTarget::MANUAL_CLARIFICATION,
                intent: $intentResolution->intent,
                urgency: $intentResolution->urgency,
                serviceTerritoryDecision: $territoryDecision,
                reason: RoutingReadinessReason::INTENT_UNKNOWN,
            );
        }

        return new ServiceRoutingDecision(
            status: ServiceRoutingStatus::ROUTABLE,
            target: $route['target'],
            intent: $intentResolution->intent,
            urgency: $intentResolution->urgency,
            serviceTerritoryDecision: $territoryDecision,
            reason: $route['reason'],
        );
    }

    /**
     * @return array{target: ServiceRouteTarget, reason: ServiceRoutingReason}|null
     */
    private function routeFor(CitizenIntent $intent): ?array
    {
        return match ($intent) {
            CitizenIntent::REPORT => [
                'target' => ServiceRouteTarget::REPORT_SERVICE,
                'reason' => ServiceRoutingReason::ROUTED_TO_REPORT,
            ],
            CitizenIntent::EMERGENCY => [
                'target' => ServiceRouteTarget::EMERGENCY_SERVICE,
                'reason' => ServiceRoutingReason::ROUTED_TO_EMERGENCY,
            ],
            CitizenIntent::LETTER => [
                'target' => ServiceRouteTarget::LETTER_SERVICE,
                'reason' => ServiceRoutingReason::ROUTED_TO_LETTER,
            ],
            CitizenIntent::INFORMATION => [
                'target' => ServiceRouteTarget::INFORMATION_SERVICE,
                'reason' => ServiceRoutingReason::ROUTED_TO_INFORMATION,
            ],
            CitizenIntent::ASPIRATION => [
                'target' => ServiceRouteTarget::ASPIRATION_SERVICE,
                'reason' => ServiceRoutingReason::ROUTED_TO_ASPIRATION,
            ],
            CitizenIntent::UNKNOWN => null,
        };
    }
}
