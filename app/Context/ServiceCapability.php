<?php

namespace App\Context;

use App\Enums\CapabilityRequirement;
use App\Enums\HumanOversightRequirement;
use App\Enums\ServiceActionType;
use App\Enums\ServiceRouteTarget;

/**
 * Immutable description of what a routed service domain may conceptually do.
 */
final readonly class ServiceCapability
{
    public function __construct(
        public ServiceRouteTarget $routeTarget,
        public ServiceActionType $actionType,
        public CapabilityRequirement $identityRequirement,
        public CapabilityRequirement $serviceTerritoryRequirement,
        public HumanOversightRequirement $humanOversight,
    ) {}
}
