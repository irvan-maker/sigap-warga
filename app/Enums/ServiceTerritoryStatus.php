<?php

namespace App\Enums;

enum ServiceTerritoryStatus: string
{
    case RESOLVED = 'resolved';
    case OPTIONAL = 'optional';
    case UNRESOLVED = 'unresolved';
}
