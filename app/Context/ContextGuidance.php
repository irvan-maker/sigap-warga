<?php

namespace App\Context;

use App\Enums\ContextGuidanceReason;
use App\Enums\ContextReadinessStatus;
use App\Enums\NextContextRequirement;

final readonly class ContextGuidance
{
    public function __construct(
        public ContextReadinessStatus $readinessStatus,
        public NextContextRequirement $nextRequirement,
        public bool $canProceed,
        public ContextGuidanceReason $reasonCode,
    ) {}
}
