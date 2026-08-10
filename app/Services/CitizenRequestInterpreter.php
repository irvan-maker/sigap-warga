<?php

namespace App\Services;

use App\Context\CitizenRequestUnderstanding;
use App\Context\EntryContext;
use App\Models\Rt;

/**
 * Interprets a natural-language request and stops before service routing.
 */
class CitizenRequestInterpreter
{
    public function __construct(
        private readonly RuleBasedIntentResolver $intentResolver,
        private readonly ServiceUnderstandingOrchestrator $understandingOrchestrator,
        private readonly RoutingReadinessDiagnoser $routingReadinessDiagnoser,
    ) {}

    public function interpret(
        EntryContext $entry,
        string $message,
        ?Rt $incidentRt = null,
    ): CitizenRequestUnderstanding {
        $ruleBasedResolution = $this->intentResolver->resolveWithExplanation(
            $message,
            $incidentRt,
        );
        $entryWithMessage = new EntryContext(
            channel: $entry->channel,
            message: $message,
            phone: $entry->phone,
            rt: $entry->rt,
        );
        $serviceUnderstanding = $this->understandingOrchestrator->understand(
            $entryWithMessage,
            $ruleBasedResolution->resolution,
        );
        $routingReadiness = $this->routingReadinessDiagnoser->diagnose($serviceUnderstanding);

        return new CitizenRequestUnderstanding(
            ruleBasedResolution: $ruleBasedResolution,
            serviceUnderstanding: $serviceUnderstanding,
            routingReadiness: $routingReadiness,
        );
    }
}
