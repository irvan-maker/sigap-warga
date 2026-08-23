<?php

namespace App\Services;

use App\Context\CitizenServiceUnderstanding;
use App\Context\RoutingReadiness;
use App\Enums\CitizenIntent;
use App\Enums\ContextGuidanceReason;
use App\Enums\RoutingReadinessReason;
use App\Enums\RoutingReadinessStatus;
use App\Enums\ServiceTerritoryStatus;

class RoutingReadinessDiagnoser
{
    public function diagnose(CitizenServiceUnderstanding $understanding): RoutingReadiness
    {
        $contextBlocker = $this->contextBlocker($understanding);

        if ($contextBlocker !== null) {
            return $this->blocked($contextBlocker);
        }

        if ($understanding->intentResolution->intent === CitizenIntent::UNKNOWN) {
            return $this->blocked(RoutingReadinessReason::INTENT_UNKNOWN);
        }

        if (! $understanding->isIntentUrgencyValid()) {
            return $this->blocked(RoutingReadinessReason::INTENT_URGENCY_INVALID);
        }

        if ($understanding->serviceTerritoryDecision->status === ServiceTerritoryStatus::UNRESOLVED) {
            return $this->blocked(RoutingReadinessReason::SERVICE_TERRITORY_UNRESOLVED);
        }

        return new RoutingReadiness(
            status: RoutingReadinessStatus::READY,
            reason: RoutingReadinessReason::READY,
        );
    }

    private function contextBlocker(
        CitizenServiceUnderstanding $understanding,
    ): ?RoutingReadinessReason {
        if ($understanding->isContextReady()
            || $understanding->isTerritoryConflictClarifiedByIncident()
            || $understanding->isTerritoryConflictAcceptedAtDomicile()) {
            return null;
        }

        return match ($understanding->contextResult->guidance->reasonCode) {
            ContextGuidanceReason::IDENTITY_REQUIRED => RoutingReadinessReason::IDENTITY_REQUIRED,
            ContextGuidanceReason::TERRITORY_REQUIRED => RoutingReadinessReason::TERRITORY_REQUIRED,
            ContextGuidanceReason::IDENTITY_AND_TERRITORY_REQUIRED => RoutingReadinessReason::IDENTITY_AND_TERRITORY_REQUIRED,
            ContextGuidanceReason::TERRITORY_CONFIRMATION_REQUIRED => RoutingReadinessReason::TERRITORY_CONFIRMATION_REQUIRED,
            ContextGuidanceReason::IDENTITY_REACTIVATION_REQUIRED => RoutingReadinessReason::IDENTITY_INACTIVE,
            ContextGuidanceReason::CONTEXT_READY => RoutingReadinessReason::CONTEXT_INCOMPLETE,
        };
    }

    private function blocked(RoutingReadinessReason $reason): RoutingReadiness
    {
        return new RoutingReadiness(
            status: RoutingReadinessStatus::BLOCKED,
            reason: $reason,
        );
    }
}
