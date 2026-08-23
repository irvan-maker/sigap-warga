<?php

namespace App\Context;

use App\Enums\CitizenIntent;
use App\Enums\ServiceTerritoryStatus;
use App\Enums\TerritoryPurpose;

/**
 * The minimum typed understanding available before service routing.
 *
 * A true canProceedToRouting() result only permits evaluation by a future
 * router. It does not authorize execution of a service or workflow.
 */
final readonly class CitizenServiceUnderstanding
{
    public function __construct(
        public ContextEngineResult $contextResult,
        public IntentResolution $intentResolution,
        public IntentUrgencyValidation $intentUrgencyValidation,
        public ServiceTerritoryDecision $serviceTerritoryDecision,
    ) {}

    public function isContextReady(): bool
    {
        return $this->contextResult->guidance->canProceed;
    }

    public function isIntentUrgencyValid(): bool
    {
        return $this->intentUrgencyValidation->isValid();
    }

    public function hasResolvedServiceTerritory(): bool
    {
        return $this->serviceTerritoryDecision->isResolved();
    }

    public function canProceedToRouting(): bool
    {
        $contextAllowsProceeding = $this->isContextReady()
            || $this->isTerritoryConflictClarifiedByIncident()
            || $this->isTerritoryConflictAcceptedAtDomicile();

        return $contextAllowsProceeding
            && $this->isIntentUrgencyValid()
            && $this->intentResolution->intent !== CitizenIntent::UNKNOWN
            && $this->serviceTerritoryDecision->status !== ServiceTerritoryStatus::UNRESOLVED;
    }

    /**
     * Intent can clarify a pre-intent identity/entry conflict when an explicit
     * incident location confirms the entry territory selected by the policy.
     */
    public function isTerritoryConflictClarifiedByIncident(): bool
    {
        $context = $this->contextResult->context;
        $incidentRt = $this->intentResolution->incidentRt;

        return $context->hasTerritoryConflict()
            && $incidentRt !== null
            && $context->entryRt?->is($incidentRt) === true
            && $this->serviceTerritoryDecision->preferredSource === TerritoryPurpose::INCIDENT
            && $this->serviceTerritoryDecision->preferredRt?->is($incidentRt) === true;
    }

    public function isTerritoryConflictAcceptedAtDomicile(): bool
    {
        $context = $this->contextResult->context;

        return $this->intentResolution->intent === CitizenIntent::REPORT
            && $context->hasTerritoryConflict()
            && $context->citizen?->is_active === true
            && $context->identityRt !== null
            && $this->serviceTerritoryDecision->preferredSource === TerritoryPurpose::IDENTITY
            && $this->serviceTerritoryDecision->preferredRt?->is($context->identityRt) === true;
    }
}
