<?php

namespace App\Services;

use App\Context\ServiceContext;
use App\Enums\ContextReadinessStatus;

class ContextReadinessPolicy
{
    public function evaluate(ServiceContext $context): ContextReadinessStatus
    {
        if ($context->citizen !== null && ! $context->citizen->is_active) {
            return ContextReadinessStatus::IDENTITY_INACTIVE;
        }

        if ($context->hasTerritoryConflict()) {
            return ContextReadinessStatus::TERRITORY_CONFLICT;
        }

        $hasIdentity = $context->hasResolvedIdentity();
        $contextTerritory = $context->resolvedContextTerritory();
        $hasValidTerritory = $contextTerritory !== null && $contextTerritory->is_active !== false;

        if ($hasIdentity && $hasValidTerritory) {
            return ContextReadinessStatus::READY;
        }

        if ($hasIdentity) {
            return ContextReadinessStatus::NEEDS_TERRITORY;
        }

        if ($hasValidTerritory) {
            return ContextReadinessStatus::NEEDS_IDENTITY;
        }

        return ContextReadinessStatus::NEEDS_IDENTITY_AND_TERRITORY;
    }
}
