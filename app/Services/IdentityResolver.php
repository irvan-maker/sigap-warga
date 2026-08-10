<?php

namespace App\Services;

use App\Context\ServiceContext;
use App\Enums\TerritoryResolutionStatus;
use App\Models\Citizen;
use App\Support\PhoneNumberNormalizer;

class IdentityResolver
{
    public function __construct(
        private readonly PhoneNumberNormalizer $phoneNumberNormalizer,
    ) {}

    public function resolve(
        string $phone,
        ?string $channel = null,
        ?string $message = null,
    ): ServiceContext {
        $normalizedPhone = $this->phoneNumberNormalizer->normalize($phone);
        $citizen = Citizen::query()
            ->with('rt')
            ->where('phone_normalized', $normalizedPhone)
            ->first();

        return new ServiceContext(
            citizen: $citizen,
            rt: $citizen?->rt,
            identityRt: $citizen?->rt,
            channel: $channel,
            message: $message,
            territoryStatus: $citizen?->rt === null
                ? TerritoryResolutionStatus::UNRESOLVED
                : TerritoryResolutionStatus::RESOLVED_FROM_IDENTITY,
        );
    }
}
