<?php

namespace App\Context;

use App\Enums\RoutingReadinessReason;
use App\Enums\ServiceExecutionEligibility;
use App\Enums\ServiceRoutingReason;

/**
 * Capability policy output. This is the boundary before future execution.
 */
final readonly class ServiceCapabilityDecision
{
    public function __construct(
        public ServiceCapability $capability,
        public ServiceRoutingDecision $routingDecision,
        public ServiceExecutionEligibility $executionEligibility,
        public ServiceRoutingReason|RoutingReadinessReason $reason,
    ) {}

    public function isExecutable(): bool
    {
        return $this->executionEligibility === ServiceExecutionEligibility::ELIGIBLE;
    }
}
