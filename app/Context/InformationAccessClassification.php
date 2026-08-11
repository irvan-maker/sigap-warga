<?php

namespace App\Context;

use App\Enums\CapabilityRequirement;
use App\Enums\InformationAccessLevel;
use App\Enums\InformationCategory;
use App\Enums\InformationClassificationReason;

/**
 * Access requirement classification only; it contains no retrieved data.
 */
final readonly class InformationAccessClassification
{
    public function __construct(
        public InformationAccessLevel $accessLevel,
        public InformationCategory $category,
        public CapabilityRequirement $identityRequirement,
        public InformationClassificationReason $reason,
    ) {}

    public function allowsAnonymousAccess(): bool
    {
        return $this->accessLevel === InformationAccessLevel::PUBLIC;
    }
}
