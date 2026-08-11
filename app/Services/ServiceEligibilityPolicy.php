<?php

namespace App\Services;

use App\Context\CitizenRequestUnderstanding;
use App\Context\ServiceCapability;
use App\Context\ServiceEligibilityDecision;
use App\Enums\CapabilityRequirement;
use App\Enums\ContextGuidanceReason;
use App\Enums\MissingServiceRequirement;
use App\Enums\ServiceEligibilityReason;
use App\Enums\ServiceEligibilityStatus;
use App\Enums\ServiceRouteTarget;
use App\Enums\ServiceTerritoryStatus;

/**
 * Reconciles generic readiness with service-specific requirements.
 *
 * It reads resolved facts only and never changes identity or territory.
 */
final class ServiceEligibilityPolicy
{
    public function __construct(
        private readonly ServiceCapabilityPolicy $capabilityPolicy,
    ) {}

    public function evaluate(CitizenRequestUnderstanding $understanding): ServiceEligibilityDecision
    {
        $serviceUnderstanding = $understanding->serviceUnderstanding;
        $intent = $serviceUnderstanding->intentResolution->intent;
        $target = ServiceRouteTarget::forIntent($intent);

        if ($target === null || ! $serviceUnderstanding->isIntentUrgencyValid()) {
            return $this->blocked(
                reason: ServiceEligibilityReason::INVALID_INTENT_OR_ROUTING,
            );
        }

        $capability = $this->capabilityPolicy->forTarget($target);

        if ($this->hasUnresolvedContextConflict($understanding)) {
            return $this->blocked(
                reason: ServiceEligibilityReason::ROUTING_NOT_READY,
                target: $target,
                capability: $capability,
            );
        }

        $context = $serviceUnderstanding->contextResult->context;
        $identitySatisfied = $capability->identityRequirement !== CapabilityRequirement::REQUIRED
            || ($context->citizen !== null && $context->citizen->is_active);
        $territoryDecision = $serviceUnderstanding->serviceTerritoryDecision;
        $territorySatisfied = $capability->serviceTerritoryRequirement !== CapabilityRequirement::REQUIRED
            || ($territoryDecision->status === ServiceTerritoryStatus::RESOLVED
                && $territoryDecision->preferredRt?->is_active !== false);

        if (! $identitySatisfied || ! $territorySatisfied) {
            return $this->missingRequirements(
                target: $target,
                capability: $capability,
                identityMissing: ! $identitySatisfied,
                territoryMissing: ! $territorySatisfied,
            );
        }

        return new ServiceEligibilityDecision(
            status: ServiceEligibilityStatus::ELIGIBLE,
            routeTarget: $target,
            capability: $capability,
            reason: ServiceEligibilityReason::ELIGIBLE,
        );
    }

    private function hasUnresolvedContextConflict(CitizenRequestUnderstanding $understanding): bool
    {
        $serviceUnderstanding = $understanding->serviceUnderstanding;
        $reason = $serviceUnderstanding->contextResult->guidance->reasonCode;

        if ($reason === ContextGuidanceReason::TERRITORY_CONFIRMATION_REQUIRED) {
            return ! $serviceUnderstanding->isTerritoryConflictClarifiedByIncident();
        }

        return $reason === ContextGuidanceReason::IDENTITY_REACTIVATION_REQUIRED;
    }

    private function missingRequirements(
        ServiceRouteTarget $target,
        ServiceCapability $capability,
        bool $identityMissing,
        bool $territoryMissing,
    ): ServiceEligibilityDecision {
        if ($identityMissing && $territoryMissing && $target !== ServiceRouteTarget::LETTER_SERVICE) {
            return $this->blocked(
                reason: ServiceEligibilityReason::IDENTITY_AND_TERRITORY_REQUIRED,
                target: $target,
                capability: $capability,
                missingRequirement: MissingServiceRequirement::IDENTITY_AND_TERRITORY,
            );
        }

        if ($identityMissing) {
            return $this->blocked(
                reason: ServiceEligibilityReason::IDENTITY_REQUIRED,
                target: $target,
                capability: $capability,
                missingRequirement: MissingServiceRequirement::IDENTITY,
            );
        }

        return $this->blocked(
            reason: ServiceEligibilityReason::TERRITORY_REQUIRED,
            target: $target,
            capability: $capability,
            missingRequirement: MissingServiceRequirement::TERRITORY,
        );
    }

    private function blocked(
        ServiceEligibilityReason $reason,
        ?ServiceRouteTarget $target = null,
        ?ServiceCapability $capability = null,
        ?MissingServiceRequirement $missingRequirement = null,
    ): ServiceEligibilityDecision {
        return new ServiceEligibilityDecision(
            status: ServiceEligibilityStatus::BLOCKED,
            routeTarget: $target,
            capability: $capability,
            reason: $reason,
            missingRequirement: $missingRequirement,
        );
    }
}
