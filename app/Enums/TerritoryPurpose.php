<?php

namespace App\Enums;

enum TerritoryPurpose: string
{
    case IDENTITY = 'identity';
    case ENTRY = 'entry';
    case INCIDENT = 'incident';
    case SERVICE = 'service';
}
