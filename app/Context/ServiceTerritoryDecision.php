<?php

namespace App\Context;

use App\Enums\CitizenIntent;
use App\Enums\ServiceTerritoryStatus;
use App\Enums\TerritoryPurpose;
use App\Models\Rt;

/**
 * A domain preference only; it does not select a recipient or workflow.
 */
final readonly class ServiceTerritoryDecision
{
    public function __construct(
        public CitizenIntent $intent,
        public ServiceTerritoryStatus $status,
        public ?Rt $preferredRt = null,
        public ?TerritoryPurpose $preferredSource = null,
    ) {}

    public function isResolved(): bool
    {
        return $this->status === ServiceTerritoryStatus::RESOLVED;
    }
}
