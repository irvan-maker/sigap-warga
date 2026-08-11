<?php

namespace App\Context;

use App\Enums\CitizenIntent;
use App\Enums\RoutingReadinessReason;
use App\Enums\ServiceEligibilityReason;
use App\Enums\ServiceRouteTarget;
use App\Enums\ServiceRoutingReason;
use App\Enums\ServiceRoutingStatus;
use App\Enums\UrgencyLevel;

/**
 * Immutable, side-effect-free selection of a service domain.
 *
 * This decision does not authorize or execute any service workflow.
 */
final readonly class ServiceRoutingDecision
{
    public function __construct(
        public ServiceRoutingStatus $status,
        public ServiceRouteTarget $target,
        public CitizenIntent $intent,
        public UrgencyLevel $urgency,
        public ServiceTerritoryDecision $serviceTerritoryDecision,
        public ServiceRoutingReason|RoutingReadinessReason|ServiceEligibilityReason $reason,
    ) {}

    public function canRoute(): bool
    {
        return $this->status === ServiceRoutingStatus::ROUTABLE;
    }
}
