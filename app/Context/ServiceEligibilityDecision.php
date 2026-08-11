<?php

namespace App\Context;

use App\Enums\MissingServiceRequirement;
use App\Enums\ServiceEligibilityReason;
use App\Enums\ServiceEligibilityStatus;
use App\Enums\ServiceRouteTarget;

/**
 * Service-specific eligibility derived from preserved understanding facts.
 */
final readonly class ServiceEligibilityDecision
{
    public function __construct(
        public ServiceEligibilityStatus $status,
        public ?ServiceRouteTarget $routeTarget,
        public ?ServiceCapability $capability,
        public ServiceEligibilityReason $reason,
        public ?MissingServiceRequirement $missingRequirement = null,
    ) {}

    public function isEligible(): bool
    {
        return $this->status === ServiceEligibilityStatus::ELIGIBLE;
    }
}
