<?php

namespace App\Context;

use App\Enums\TerritoryResolutionStatus;
use App\Models\Citizen;
use App\Models\Rt;

/**
 * Facts resolved before intent and service routing.
 *
 * The $rt property is a resolved context territory candidate. It is not the
 * final service territory, which can only be decided after the service need
 * is understood.
 */
final readonly class ServiceContext
{
    public function __construct(
        public ?Citizen $citizen = null,
        public ?Rt $rt = null,
        public ?Rt $identityRt = null,
        public ?Rt $entryRt = null,
        public ?string $channel = null,
        public ?string $intent = null,
        public ?string $message = null,
        public TerritoryResolutionStatus $territoryStatus = TerritoryResolutionStatus::UNRESOLVED,
    ) {}

    public function hasResolvedIdentity(): bool
    {
        return $this->citizen !== null;
    }

    public function hasResolvedTerritory(): bool
    {
        return $this->rt !== null;
    }

    public function resolvedContextTerritory(): ?Rt
    {
        return $this->rt;
    }

    public function hasTerritoryConflict(): bool
    {
        return $this->territoryStatus === TerritoryResolutionStatus::CONFLICT;
    }

    public function hasInvalidEntryTerritory(): bool
    {
        return $this->entryRt !== null && $this->entryRt->is_active === false;
    }

    public function hasResolvedIntent(): bool
    {
        return $this->intent !== null;
    }
}
