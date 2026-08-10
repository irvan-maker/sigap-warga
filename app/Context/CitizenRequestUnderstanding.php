<?php

namespace App\Context;

/**
 * Complete, side-effect-free understanding of one citizen request.
 *
 * This result is the boundary immediately before a future service router.
 */
final readonly class CitizenRequestUnderstanding
{
    public function __construct(
        public RuleBasedIntentResolution $ruleBasedResolution,
        public CitizenServiceUnderstanding $serviceUnderstanding,
        public RoutingReadiness $routingReadiness,
    ) {}
}
