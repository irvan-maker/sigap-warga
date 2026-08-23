<?php

namespace App\Context;

use App\Enums\InformationCategory;
use App\Enums\InformationSubjectRelationship;

/**
 * Caller-supplied subject claim without protected data or resolved ownership.
 */
final readonly class ProtectedInformationSubject
{
    public function __construct(
        public InformationCategory $category,
        public InformationSubjectRelationship $relationship,
        public ?string $opaqueIdentifier = null,
    ) {}
}
