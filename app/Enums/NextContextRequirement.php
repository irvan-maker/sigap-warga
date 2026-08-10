<?php

namespace App\Enums;

enum NextContextRequirement: string
{
    case NONE = 'none';
    case IDENTITY = 'identity';
    case TERRITORY = 'territory';
    case IDENTITY_AND_TERRITORY = 'identity_and_territory';
    case TERRITORY_CONFIRMATION = 'territory_confirmation';
    case IDENTITY_REACTIVATION = 'identity_reactivation';
}
