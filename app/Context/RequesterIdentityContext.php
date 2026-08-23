<?php

namespace App\Context;

use App\Enums\RequesterIdentityType;
use App\Models\Citizen;
use App\Models\User;

/**
 * Identity facts already supplied by the caller; no identity is resolved here.
 */
final readonly class RequesterIdentityContext
{
    private function __construct(
        public RequesterIdentityType $type,
        public ?Citizen $citizen = null,
        public ?User $staffActor = null,
        public ?StaffScopeContext $staffScope = null,
    ) {}

    public static function unknown(): self
    {
        return new self(RequesterIdentityType::UNKNOWN);
    }

    public static function knownCitizen(Citizen $citizen): self
    {
        return new self(
            type: RequesterIdentityType::KNOWN_CITIZEN,
            citizen: $citizen,
        );
    }

    public static function staff(User $actor, StaffScopeContext $scope): self
    {
        return new self(
            type: RequesterIdentityType::STAFF,
            staffActor: $actor,
            staffScope: $scope,
        );
    }
}
