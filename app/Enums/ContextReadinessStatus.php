<?php

namespace App\Enums;

enum ContextReadinessStatus: string
{
    case READY = 'ready';
    case NEEDS_IDENTITY = 'needs_identity';
    case NEEDS_TERRITORY = 'needs_territory';
    case NEEDS_IDENTITY_AND_TERRITORY = 'needs_identity_and_territory';
    case TERRITORY_CONFLICT = 'territory_conflict';
    case IDENTITY_INACTIVE = 'identity_inactive';
}
