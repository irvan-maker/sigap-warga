<?php

namespace App\Services;

use App\Context\CitizenServiceUnderstanding;
use App\Context\EntryContext;
use App\Context\IntentResolution;
use App\Context\TerritoryCandidates;

/**
 * Builds domain understanding and stops before service routing.
 */
class ServiceUnderstandingOrchestrator
{
    public function __construct(
        private readonly ContextEngine $contextEngine,
        private readonly IntentUrgencyPolicy $intentUrgencyPolicy,
        private readonly ServiceTerritoryPolicy $serviceTerritoryPolicy,
    ) {}

    public function understand(
        EntryContext $entry,
        IntentResolution $intentResolution,
    ): CitizenServiceUnderstanding {
        $contextResult = $this->contextEngine->resolve($entry);
        $intentUrgencyValidation = $this->intentUrgencyPolicy->validate($intentResolution);
        $context = $contextResult->context;
        $territoryDecision = $this->serviceTerritoryPolicy->decide(
            $intentResolution->intent,
            new TerritoryCandidates(
                identityRt: $context->identityRt,
                entryRt: $context->entryRt,
                incidentRt: $intentResolution->incidentRt,
            ),
        );

        return new CitizenServiceUnderstanding(
            contextResult: $contextResult,
            intentResolution: $intentResolution,
            intentUrgencyValidation: $intentUrgencyValidation,
            serviceTerritoryDecision: $territoryDecision,
        );
    }
}
