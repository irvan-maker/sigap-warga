<?php

namespace App\Context;

use App\Models\Citizen;
use DateTimeImmutable;

/**
 * Trusted, channel-neutral facts for executing a citizen REPORT decision.
 *
 * The source and correlation ID are carried for future durable audit and
 * idempotency support; the current schema cannot persist them yet.
 */
final readonly class CreateCitizenReportCommand
{
    public function __construct(
        public Citizen $requester,
        public ServiceRoutingDecision $routingDecision,
        public string $title,
        public string $description,
        public DateTimeImmutable $reportedAt,
        public string $source,
        public string $correlationRequestId,
    ) {}
}
