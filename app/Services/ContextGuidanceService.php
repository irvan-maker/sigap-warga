<?php

namespace App\Services;

use App\Context\ContextGuidance;
use App\Enums\ContextGuidanceReason;
use App\Enums\ContextReadinessStatus;
use App\Enums\NextContextRequirement;

class ContextGuidanceService
{
    public function decide(ContextReadinessStatus $readinessStatus): ContextGuidance
    {
        return match ($readinessStatus) {
            ContextReadinessStatus::READY => new ContextGuidance(
                readinessStatus: $readinessStatus,
                nextRequirement: NextContextRequirement::NONE,
                canProceed: true,
                reasonCode: ContextGuidanceReason::CONTEXT_READY,
            ),
            ContextReadinessStatus::NEEDS_IDENTITY => new ContextGuidance(
                readinessStatus: $readinessStatus,
                nextRequirement: NextContextRequirement::IDENTITY,
                canProceed: false,
                reasonCode: ContextGuidanceReason::IDENTITY_REQUIRED,
            ),
            ContextReadinessStatus::NEEDS_TERRITORY => new ContextGuidance(
                readinessStatus: $readinessStatus,
                nextRequirement: NextContextRequirement::TERRITORY,
                canProceed: false,
                reasonCode: ContextGuidanceReason::TERRITORY_REQUIRED,
            ),
            ContextReadinessStatus::NEEDS_IDENTITY_AND_TERRITORY => new ContextGuidance(
                readinessStatus: $readinessStatus,
                nextRequirement: NextContextRequirement::IDENTITY_AND_TERRITORY,
                canProceed: false,
                reasonCode: ContextGuidanceReason::IDENTITY_AND_TERRITORY_REQUIRED,
            ),
            ContextReadinessStatus::TERRITORY_CONFLICT => new ContextGuidance(
                readinessStatus: $readinessStatus,
                nextRequirement: NextContextRequirement::TERRITORY_CONFIRMATION,
                canProceed: false,
                reasonCode: ContextGuidanceReason::TERRITORY_CONFIRMATION_REQUIRED,
            ),
            ContextReadinessStatus::IDENTITY_INACTIVE => new ContextGuidance(
                readinessStatus: $readinessStatus,
                nextRequirement: NextContextRequirement::IDENTITY_REACTIVATION,
                canProceed: false,
                reasonCode: ContextGuidanceReason::IDENTITY_REACTIVATION_REQUIRED,
            ),
        };
    }
}
