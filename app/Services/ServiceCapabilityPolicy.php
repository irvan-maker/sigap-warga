<?php

namespace App\Services;

use App\Context\ServiceCapability;
use App\Context\ServiceCapabilityDecision;
use App\Context\ServiceRoutingDecision;
use App\Enums\CapabilityRequirement;
use App\Enums\HumanOversightRequirement;
use App\Enums\ServiceActionType;
use App\Enums\ServiceExecutionEligibility;
use App\Enums\ServiceRouteTarget;

/**
 * Describes capability without executing, dispatching, or mutating anything.
 */
final class ServiceCapabilityPolicy
{
    public function decide(ServiceRoutingDecision $routingDecision): ServiceCapabilityDecision
    {
        return new ServiceCapabilityDecision(
            capability: $this->capabilityFor($routingDecision->target),
            routingDecision: $routingDecision,
            executionEligibility: $routingDecision->canRoute()
                ? ServiceExecutionEligibility::ELIGIBLE
                : ServiceExecutionEligibility::NOT_EXECUTABLE,
            reason: $routingDecision->reason,
        );
    }

    private function capabilityFor(ServiceRouteTarget $target): ServiceCapability
    {
        return match ($target) {
            ServiceRouteTarget::REPORT_SERVICE => new ServiceCapability(
                routeTarget: $target,
                actionType: ServiceActionType::CREATE_CASE,
                identityRequirement: CapabilityRequirement::REQUIRED,
                serviceTerritoryRequirement: CapabilityRequirement::REQUIRED,
                humanOversight: HumanOversightRequirement::VERIFICATION,
            ),
            ServiceRouteTarget::EMERGENCY_SERVICE => new ServiceCapability(
                routeTarget: $target,
                actionType: ServiceActionType::INITIATE_EMERGENCY_RESPONSE,
                identityRequirement: CapabilityRequirement::OPTIONAL,
                serviceTerritoryRequirement: CapabilityRequirement::REQUIRED,
                humanOversight: HumanOversightRequirement::OPERATOR_REQUIRED,
            ),
            ServiceRouteTarget::LETTER_SERVICE => new ServiceCapability(
                routeTarget: $target,
                actionType: ServiceActionType::INITIATE_ADMINISTRATIVE_SERVICE,
                identityRequirement: CapabilityRequirement::REQUIRED,
                serviceTerritoryRequirement: CapabilityRequirement::REQUIRED,
                humanOversight: HumanOversightRequirement::APPROVAL,
            ),
            ServiceRouteTarget::INFORMATION_SERVICE => new ServiceCapability(
                routeTarget: $target,
                actionType: ServiceActionType::PROVIDE_INFORMATION,
                identityRequirement: CapabilityRequirement::OPTIONAL,
                serviceTerritoryRequirement: CapabilityRequirement::OPTIONAL,
                humanOversight: HumanOversightRequirement::NONE,
            ),
            ServiceRouteTarget::ASPIRATION_SERVICE => new ServiceCapability(
                routeTarget: $target,
                actionType: ServiceActionType::REGISTER_ASPIRATION,
                identityRequirement: CapabilityRequirement::REQUIRED,
                serviceTerritoryRequirement: CapabilityRequirement::REQUIRED,
                humanOversight: HumanOversightRequirement::VERIFICATION,
            ),
            ServiceRouteTarget::MANUAL_CLARIFICATION => new ServiceCapability(
                routeTarget: $target,
                actionType: ServiceActionType::REQUEST_CLARIFICATION,
                identityRequirement: CapabilityRequirement::NOT_APPLICABLE,
                serviceTerritoryRequirement: CapabilityRequirement::NOT_APPLICABLE,
                humanOversight: HumanOversightRequirement::OPERATOR_REQUIRED,
            ),
        };
    }
}
