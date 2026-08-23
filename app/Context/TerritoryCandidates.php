<?php

namespace App\Context;

use App\Models\Rt;

final readonly class TerritoryCandidates
{
    public function __construct(
        public ?Rt $identityRt = null,
        public ?Rt $entryRt = null,
        public ?Rt $incidentRt = null,
    ) {}
}
