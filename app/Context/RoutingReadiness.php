<?php

namespace App\Context;

use App\Enums\RoutingReadinessReason;
use App\Enums\RoutingReadinessStatus;

final readonly class RoutingReadiness
{
    public function __construct(
        public RoutingReadinessStatus $status,
        public RoutingReadinessReason $reason,
    ) {}

    public function canProceed(): bool
    {
        return $this->status === RoutingReadinessStatus::READY;
    }
}
