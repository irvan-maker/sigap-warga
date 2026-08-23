<?php

namespace App\Context;

use App\Enums\TrustedInboundProcessingOutcome;
use App\Enums\TrustedInboundProcessingReason;
use App\Models\InboundRequest;
use App\Models\Report;

final readonly class TrustedInboundProcessingResult
{
    public function __construct(
        public InboundRequest $inboundRequest,
        public TrustedInboundProcessingOutcome $outcome,
        public TrustedInboundProcessingReason $reason,
        public ?CitizenRequestUnderstanding $understanding = null,
        public ?ServiceEligibilityDecision $eligibilityDecision = null,
        public ?ServiceRoutingDecision $routingDecision = null,
        public ?Report $report = null,
    ) {}
}
