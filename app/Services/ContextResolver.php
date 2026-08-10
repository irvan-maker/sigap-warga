<?php

namespace App\Services;

use App\Context\EntryContext;
use App\Context\ServiceContext;
use App\Enums\TerritoryResolutionStatus;
use App\Models\Rt;

class ContextResolver
{
    public function __construct(
        private readonly IdentityResolver $identityResolver,
    ) {}

    public function resolve(EntryContext $entry): ServiceContext
    {
        $identityContext = $entry->phone === null
            ? new ServiceContext
            : $this->identityResolver->resolve($entry->phone);

        $identityRt = $identityContext->identityRt ?? $identityContext->rt;
        [$rt, $territoryStatus] = $this->resolveTerritory(
            $this->validTerritory($identityRt),
            $this->validTerritory($entry->rt),
        );

        return new ServiceContext(
            citizen: $identityContext->citizen,
            rt: $rt,
            identityRt: $identityRt,
            entryRt: $entry->rt,
            channel: $entry->channel,
            intent: $identityContext->intent,
            message: $entry->message,
            territoryStatus: $territoryStatus,
        );
    }

    /**
     * @return array{Rt|null, TerritoryResolutionStatus}
     */
    private function resolveTerritory(?Rt $identityRt, ?Rt $entryRt): array
    {
        if ($identityRt !== null && $entryRt !== null) {
            if ($identityRt->is($entryRt)) {
                return [$identityRt, TerritoryResolutionStatus::VERIFIED];
            }

            return [null, TerritoryResolutionStatus::CONFLICT];
        }

        if ($identityRt !== null) {
            return [$identityRt, TerritoryResolutionStatus::RESOLVED_FROM_IDENTITY];
        }

        if ($entryRt !== null) {
            return [$entryRt, TerritoryResolutionStatus::RESOLVED_FROM_ENTRY];
        }

        return [null, TerritoryResolutionStatus::UNRESOLVED];
    }

    private function validTerritory(?Rt $rt): ?Rt
    {
        return $rt !== null && $rt->is_active !== false ? $rt : null;
    }
}
