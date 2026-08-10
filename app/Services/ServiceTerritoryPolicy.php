<?php

namespace App\Services;

use App\Context\ServiceTerritoryDecision;
use App\Context\TerritoryCandidates;
use App\Enums\CitizenIntent;
use App\Enums\ServiceTerritoryStatus;
use App\Enums\TerritoryPurpose;
use App\Models\Rt;

/**
 * Selects a conceptual service-territory preference without routing work.
 */
class ServiceTerritoryPolicy
{
    public function decide(
        CitizenIntent $intent,
        TerritoryCandidates $territories,
    ): ServiceTerritoryDecision {
        return match ($intent) {
            CitizenIntent::LETTER => $this->fromCandidate(
                $intent,
                $territories->identityRt,
                TerritoryPurpose::IDENTITY,
            ),
            CitizenIntent::REPORT => $this->firstAvailable($intent, [
                TerritoryPurpose::INCIDENT->value => $territories->incidentRt,
                TerritoryPurpose::ENTRY->value => $territories->entryRt,
            ]),
            CitizenIntent::EMERGENCY => $this->firstAvailable($intent, [
                TerritoryPurpose::INCIDENT->value => $territories->incidentRt,
                TerritoryPurpose::ENTRY->value => $territories->entryRt,
            ]),
            CitizenIntent::INFORMATION => $territories->entryRt === null
                ? $this->unresolved($intent, ServiceTerritoryStatus::OPTIONAL)
                : $this->resolved($intent, $territories->entryRt, TerritoryPurpose::ENTRY),
            CitizenIntent::ASPIRATION => $this->firstAvailable($intent, [
                TerritoryPurpose::INCIDENT->value => $territories->incidentRt,
                TerritoryPurpose::ENTRY->value => $territories->entryRt,
                TerritoryPurpose::IDENTITY->value => $territories->identityRt,
            ]),
            CitizenIntent::UNKNOWN => $this->unresolved($intent),
        };
    }

    /**
     * @param  array<string, Rt|null>  $candidates
     */
    private function firstAvailable(
        CitizenIntent $intent,
        array $candidates,
    ): ServiceTerritoryDecision {
        foreach ($candidates as $purpose => $rt) {
            if ($rt !== null) {
                return $this->resolved($intent, $rt, TerritoryPurpose::from($purpose));
            }
        }

        return $this->unresolved($intent);
    }

    private function fromCandidate(
        CitizenIntent $intent,
        ?Rt $rt,
        TerritoryPurpose $purpose,
    ): ServiceTerritoryDecision {
        return $rt === null
            ? $this->unresolved($intent)
            : $this->resolved($intent, $rt, $purpose);
    }

    private function resolved(
        CitizenIntent $intent,
        Rt $rt,
        TerritoryPurpose $purpose,
    ): ServiceTerritoryDecision {
        return new ServiceTerritoryDecision(
            intent: $intent,
            status: ServiceTerritoryStatus::RESOLVED,
            preferredRt: $rt,
            preferredSource: $purpose,
        );
    }

    private function unresolved(
        CitizenIntent $intent,
        ServiceTerritoryStatus $status = ServiceTerritoryStatus::UNRESOLVED,
    ): ServiceTerritoryDecision {
        return new ServiceTerritoryDecision(intent: $intent, status: $status);
    }
}
