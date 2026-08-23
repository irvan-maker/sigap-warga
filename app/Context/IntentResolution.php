<?php

namespace App\Context;

use App\Enums\CitizenIntent;
use App\Enums\UrgencyLevel;
use App\Models\Rt;

/**
 * Typed output contract for a future intent resolver.
 *
 * This object contains resolved domain facts only. It does not classify text,
 * select a service, or start a workflow.
 */
final readonly class IntentResolution
{
    public function __construct(
        public CitizenIntent $intent,
        public UrgencyLevel $urgency,
        public ?Rt $incidentRt = null,
    ) {}
}
