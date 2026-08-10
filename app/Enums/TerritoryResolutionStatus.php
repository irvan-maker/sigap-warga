<?php

namespace App\Enums;

enum TerritoryResolutionStatus: string
{
    case UNRESOLVED = 'unresolved';
    case RESOLVED_FROM_IDENTITY = 'resolved_from_identity';
    case RESOLVED_FROM_ENTRY = 'resolved_from_entry';
    case VERIFIED = 'verified';
    case CONFLICT = 'conflict';
}
