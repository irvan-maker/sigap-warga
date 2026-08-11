<?php

namespace App\Context;

use App\Models\Citizen;
use App\Models\InboundRequest;
use DateTimeImmutable;

/**
 * Trusted, channel-neutral facts for executing a citizen REPORT decision.
 *
 * Inbound identity and correlation are owned by a persisted inbound request.
 */
final readonly class CreateCitizenReportCommand
{
    public function __construct(
        public Citizen $requester,
        public ServiceRoutingDecision $routingDecision,
        public string $title,
        public string $description,
        public DateTimeImmutable $reportedAt,
        public InboundRequest $inboundRequest,
    ) {}
}
